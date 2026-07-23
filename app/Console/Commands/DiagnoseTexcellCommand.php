<?php

namespace App\Console\Commands;

use App\Enums\SmsProviderDriver;
use App\Services\Sms\EnsureTexcellProvider;
use App\Services\Sms\TexcellBalanceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Texcell kurulumu + kimlik/IP teşhisi (SMS göndermez).
 */
class DiagnoseTexcellCommand extends Command
{
    private const EXPECTED_PASSWORD = 'HzCzaRmBLAUt9';

    private const EXPECTED_ACCOUNT = 'CTU780';

    protected $signature = 'sms:texcell-diagnose
                            {--account= : Geçici account (DB/config yerine)}
                            {--password= : Geçici password (DB/config yerine)}
                            {--base-url= : Geçici base URL}
                            {--skip-ensure : DB düzeltmesini atla}
                            {--force-known : Bilinen CTU780 şifresini zorla dene}';

    protected $description = 'Texcell’i DB’ye yazar, getbalance test eder, şifre/IP teşhisi yapar';

    public function handle(EnsureTexcellProvider $ensure, TexcellBalanceSyncService $syncService): int
    {
        if (! $this->option('skip-ensure')) {
            $this->info('Texcell DB kaydı güncelleniyor (mock → texcell)...');
            $model = $ensure->ensure();
        } else {
            $model = \App\Models\SmsProvider::query()
                ->where('driver', SmsProviderDriver::Texcell->value)
                ->orderByDesc('is_default')
                ->first();

            if ($model === null) {
                $this->error('Texcell kaydı yok. --skip-ensure olmadan tekrar çalıştırın.');

                return self::FAILURE;
            }
        }

        $resolved = $ensure->resolvedConfig();
        $account = trim((string) ($this->option('account') ?: $resolved['account']));
        $password = trim((string) ($this->option('password') ?: $resolved['password']));
        $baseUrl = rtrim(trim((string) ($this->option('base-url') ?: $resolved['base_url'])), '/');

        if ($this->option('force-known')) {
            $account = self::EXPECTED_ACCOUNT;
            $password = self::EXPECTED_PASSWORD;
            $this->warn('force-known: bilinen CTU780 / (beklenen şifre) kullanılıyor.');
        }

        $publicIp = $this->detectPublicIp();
        $fp = $this->fingerprint($password);
        $expectedFp = $this->fingerprint(self::EXPECTED_PASSWORD);
        $passwordMatchesKnown = hash_equals($expectedFp, $fp);
        $accountMatchesKnown = strcasecmp($account, self::EXPECTED_ACCOUNT) === 0;

        $this->newLine();
        $this->info('Texcell teşhis');
        $this->line('DB kayıt: #'.$model->id.' code='.$model->code.' driver='.$model->driverValue());
        $this->line('Aktif: '.($model->is_active ? 'evet' : 'hayır').' | Varsayılan: '.($model->is_default ? 'evet' : 'hayır'));
        $this->line('Base URL: '.$baseUrl);
        $this->line('Account: '.($account !== '' ? $account : '(BOŞ)').($accountMatchesKnown ? ' (bilinen CTU780 ile aynı)' : ' (bilinen CTU780’den FARKLI)'));
        $this->line('Password uzunluk: '.strlen($password).($password === '' ? ' (BOŞ!)' : ''));
        $this->line('Password kaynağı: '.$this->detectPasswordSource($password));
        $this->line('Password parmak izi: '.$fp.($passwordMatchesKnown ? ' → bilinen şifre ile AYNI' : ' → bilinen şifreden FARKLI ('.$expectedFp.')'));
        $this->line('Encryption key: '.(trim((string) ($resolved['encryption_key'] ?? '')) !== '' ? 'DOLU' : 'boş'));
        $this->line('Sunucu public IP: '.($publicIp ?? '(alınamadı)'));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş.');

            return self::FAILURE;
        }

        if ($model->driverValue() !== SmsProviderDriver::Texcell->value || $model->code !== 'texcell') {
            $this->error('DB hâlâ Texcell değil.');

            return self::FAILURE;
        }

        $this->info('Kimlik denemeleri (şifre yazdırılmaz):');

        $probes = [
            ['label' => 'plain GET', 'mode' => 'get', 'params' => ['account' => $account, 'password' => $password]],
            ['label' => 'GET + json CT', 'mode' => 'get_json_ct', 'params' => ['account' => $account, 'password' => $password]],
            ['label' => 'POST JSON body', 'mode' => 'post_json', 'params' => ['account' => $account, 'password' => $password]],
            ['label' => 'version=1.0', 'mode' => 'get', 'params' => ['account' => $account, 'password' => $password, 'version' => '1.0']],
            ['label' => 'yanlış şifre kontrol', 'mode' => 'get', 'params' => ['account' => $account, 'password' => 'WRONG_PASSWORD_TEST_'.time()]],
        ];

        $plainStatus = null;
        $plainDesc = '';
        $wrongStatus = null;

        foreach ($probes as $probe) {
            $result = $this->probeGetbalance($baseUrl, $probe['params'], $probe['mode']);
            $this->line(sprintf(
                '  %-22s HTTP %s status=%s %s',
                $probe['label'],
                $result['http'],
                $result['status'] === null ? '?' : (string) $result['status'],
                $result['desc']
            ));

            if ($probe['label'] === 'plain GET') {
                $plainStatus = $result['status'];
                $plainDesc = strtolower($result['desc']);
            }
            if ($probe['label'] === 'yanlış şifre kontrol') {
                $wrongStatus = $result['status'];
            }

            if ($result['status'] === 0) {
                $this->newLine();
                $this->info('OK — Bu varyant çalıştı: '.$probe['label']);
                $this->persistWorkingCredentials(
                    $ensure,
                    $model,
                    (string) $probe['params']['account'],
                    (string) $probe['params']['password'],
                    $baseUrl
                );
                $sync = $syncService->syncProvider($model->fresh() ?? $model);
                if ($sync->success) {
                    $rate = app(\App\Services\Sms\TexcellCreditConverter::class)->rate();
                    $this->info(sprintf(
                        'Texcell USD: %s | Bro Per SMS: %s USD | Panel SMS adedi: %d',
                        number_format((float) ($sync->rawUsd ?? 0), 6, '.', ''),
                        number_format($rate, 4, '.', ''),
                        (int) floor((float) $sync->balance)
                    ));
                } else {
                    $this->line('Senkron uyarısı: '.$sync->errorMessage);
                }

                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->explainFailure($plainStatus, $wrongStatus, $plainDesc, $passwordMatchesKnown, $publicIp);

        return self::FAILURE;
    }

    /**
     * @param  array<string, scalar>  $params
     * @return array{http: int|string, status: int|null, desc: string}
     */
    private function probeGetbalance(string $baseUrl, array $params, string $mode = 'get'): array
    {
        try {
            $url = $baseUrl.'/getbalance';
            $response = match ($mode) {
                'post_json' => Http::timeout(30)
                    ->withBody(json_encode($params, JSON_UNESCAPED_UNICODE) ?: '{}', 'application/json;charset=utf-8')
                    ->post($url),
                'get_json_ct' => Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json;charset=utf-8'])
                    ->get($url, $params),
                default => Http::timeout(30)->get($url, $params),
            };
        } catch (\Throwable $e) {
            return ['http' => 'ERR', 'status' => null, 'desc' => $e->getMessage()];
        }

        $data = $response->json();
        $status = is_array($data) ? (int) ($data['status'] ?? -99) : null;
        $desc = is_array($data)
            ? (string) ($data['desc'] ?? $data['reason'] ?? $response->body())
            : $response->body();

        return [
            'http' => $response->status(),
            'status' => $status,
            'desc' => mb_substr($desc, 0, 80),
        ];
    }

    private function explainFailure(?int $plainStatus, ?int $wrongStatus, string $plainDesc, bool $passwordMatchesKnown, ?string $publicIp): void
    {
        $blocked = str_contains($plainDesc, 'user blocked') || str_contains($plainDesc, 'blocked');

        if ($plainStatus === -2 && $blocked) {
            $this->error('SONUÇ: Şifre DOĞRU — hesap Texcell’de BLOKELİ (user blocked).');
            if ($wrongStatus === -1) {
                $this->warn('Kanıt: yanlış şifre → -1 auth; doğru şifre → -2 user blocked.');
            }
            $this->warn('IP whitelist değil, hesap kilidi. Texcell’den CTU780 unblock isteyin.');
            $this->line('IP (bilgi): '.($publicIp ?? '?'));

            return;
        }

        if ($plainStatus === -2) {
            $this->error('IP whitelist’te değil veya erişim kısıtı (-2). Verin: '.($publicIp ?? '?'));

            return;
        }

        if ($plainStatus === -1 && $wrongStatus === -1) {
            $this->error('SONUÇ: Şifre paneli tarafında DOĞRU görünüyor ama API şifreyi ayırt etmiyor.');
            $this->warn('Texcell’e: CTU780 + IP '.($publicIp ?? '?').' → status -1');

            return;
        }

        if ($plainStatus === -1) {
            $this->error('PDF’e göre -1 = authentication error.');
            $this->warn($passwordMatchesKnown
                ? 'Şifre bilinen değerle aynı. Texcell’e CTU780 + IP '.$publicIp.' sorun.'
                : 'Şifre farklı — --force-known deneyin.');

            return;
        }

        $this->error('Beklenmeyen durum: status='.($plainStatus ?? 'null'));
    }

    private function persistWorkingCredentials(
        EnsureTexcellProvider $ensure,
        \App\Models\SmsProvider $model,
        string $account,
        string $password,
        string $baseUrl,
    ): void {
        if (strlen($password) === 32 && ctype_xdigit($password)) {
            $this->warn('md5 varyantı çalıştı; ham şifreyi Texcell’den doğrulayın.');

            return;
        }

        $config = $ensure->resolvedConfig();
        $config['account'] = $account;
        $config['password'] = $password;
        $config['base_url'] = $baseUrl;
        $model->config = $config;
        $model->save();
        $this->line('Çalışan kimlik DB’ye yazıldı.');
    }

    private function detectPasswordSource(string $password): string
    {
        if ($this->option('password') || $this->option('force-known')) {
            return 'komut satırı (--password / --force-known)';
        }

        $env = (string) env('TEXCELL_PASSWORD', '');
        if ($env !== '' && hash_equals($env, $password)) {
            return '.env TEXCELL_PASSWORD';
        }

        $cfg = (string) config('sms.texcell.password', '');
        if ($cfg !== '' && hash_equals($cfg, $password)) {
            return 'config/sms.php varsayılanı';
        }

        return 'EnsureTexcellProvider / DB birleşimi';
    }

    private function fingerprint(string $value): string
    {
        return substr(hash('sha256', $value), 0, 12);
    }

    private function detectPublicIp(): ?string
    {
        foreach (['https://ifconfig.me/ip', 'https://api.ipify.org', 'https://icanhazip.com'] as $url) {
            try {
                $ip = trim((string) Http::timeout(5)->get($url)->body());
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }
}
