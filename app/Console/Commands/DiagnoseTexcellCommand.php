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
    /** Texcell’den gelen bilinen hesap şifresi (parmak izi karşılaştırması). */
    private const EXPECTED_PASSWORD = 'EZM9lh3MVh1i';

    private const EXPECTED_ACCOUNT = 'CTU780';

    protected $signature = 'sms:texcell-diagnose
                            {--account= : Geçici account (DB/config yerine)}
                            {--password= : Geçici password (DB/config yerine)}
                            {--base-url= : Geçici base URL}
                            {--skip-ensure : DB düzeltmesini atla}
                            {--force-known : Bilinen CTU780 şifresini zorla dene (env/DB yok say)}';

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
        $passwordSource = $this->detectPasswordSource($password);
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
        $this->line('Password kaynağı: '.$passwordSource);
        $this->line('Password parmak izi: '.$fp.($passwordMatchesKnown ? ' → bilinen şifre ile AYNI' : ' → bilinen şifreden FARKLI ('.$expectedFp.')'));
        $this->line('Encryption key: '.(trim((string) ($resolved['encryption_key'] ?? '')) !== '' ? 'DOLU' : 'boş'));
        $this->line('Sunucu public IP: '.($publicIp ?? '(alınamadı)'));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş.');

            return self::FAILURE;
        }

        if ($model->driverValue() !== SmsProviderDriver::Texcell->value || $model->code !== 'texcell') {
            $this->error('DB hâlâ Texcell değil. php artisan optimize:clear');

            return self::FAILURE;
        }

        $this->info('Kimlik denemeleri (şifre yazdırılmaz):');
        $attempts = [
            'plain GET' => ['account' => $account, 'password' => $password],
            'version=1.0' => ['account' => $account, 'password' => $password, 'version' => '1.0'],
            'md5(password)' => ['account' => $account, 'password' => md5($password)],
            'yanlış şifre kontrol' => ['account' => $account, 'password' => 'WRONG_PASSWORD_TEST_'.time()],
        ];

        if (! $passwordMatchesKnown) {
            $attempts['bilinen şifre zorla'] = [
                'account' => self::EXPECTED_ACCOUNT,
                'password' => self::EXPECTED_PASSWORD,
            ];
        }

        $plainStatus = null;

        foreach ($attempts as $label => $params) {
            $result = $this->probeGetbalance($baseUrl, $params);
            $this->line(sprintf(
                '  %-22s HTTP %s status=%s %s',
                $label,
                $result['http'],
                $result['status'] === null ? '?' : (string) $result['status'],
                $result['desc']
            ));

            if ($label === 'plain GET') {
                $plainStatus = $result['status'];
            }

            if ($result['status'] === 0) {
                $this->newLine();
                $this->info('OK — Bu varyant çalıştı: '.$label);
                $workingPassword = (string) $params['password'];
                $this->persistWorkingCredentials($ensure, $model, (string) $params['account'], $workingPassword, $baseUrl);
                $sync = $syncService->syncProvider($model->fresh() ?? $model);
                $this->line($sync->success
                    ? 'Panel SMS hakkı: '.(int) floor((float) $sync->balance)
                    : 'Senkron uyarısı: '.$sync->errorMessage);

                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->explainFailure($plainStatus, $passwordMatchesKnown, $publicIp);

        return self::FAILURE;
    }

    /**
     * @param  array<string, scalar>  $params
     * @return array{http: int|string, status: int|null, desc: string}
     */
    private function probeGetbalance(string $baseUrl, array $params): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($baseUrl.'/getbalance', $params);
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

    private function explainFailure(?int $plainStatus, bool $passwordMatchesKnown, ?string $publicIp): void
    {
        if ($plainStatus === -2) {
            $this->error('IP whitelist’te değil (-2). Verin: '.($publicIp ?? '?'));

            return;
        }

        if ($plainStatus === -1) {
            $this->error('PDF’e göre -1 = authentication error (hesap/şifre). -2 olsaydı IP kısıtı olurdu.');

            if ($passwordMatchesKnown) {
                $this->warn('Paneldeki şifre, size verilen bilinen şifre ile AYNI görünüyor.');
                $this->warn('Olasılıklar: 1) Texcell hesabı/şifresi panelde farklı  2) Whitelist henüz aktif değil / yanlış IP  3) Hesap kilitli');
                $this->warn('Texcell’e sorun: CTU780 + IP '.$publicIp.' için getbalance neden -1?');
            } else {
                $this->warn('Kullanılan şifre bilinen şifreden FARKLI. .env TEXCELL_PASSWORD veya DB yanlış olabilir.');
                $this->warn('Düzeltmek için: php artisan sms:texcell-diagnose --force-known');
                $this->warn('veya: php artisan sms:texcell-diagnose --password="DOGRU_SIFRE"');
            }

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
        // md5 denemesi çalıştıysa ham şifreyi bilmiyoruz — sadece plain/bilinen kaydet
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
