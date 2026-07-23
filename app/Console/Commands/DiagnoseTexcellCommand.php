<?php

namespace App\Console\Commands;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use App\Sms\Providers\TexcellEimsSmsProvider;
use App\Sms\SmsProviderFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Texcell kimlik / bağlantı teşhisi (SMS göndermez).
 */
class DiagnoseTexcellCommand extends Command
{
    protected $signature = 'sms:texcell-diagnose
                            {--account= : Geçici account (DB yerine)}
                            {--password= : Geçici password (DB yerine)}
                            {--base-url= : Geçici base URL}';

    protected $description = 'Texcell getbalance ile kimlik/IP bağlantısını test eder (SMS göndermez)';

    public function handle(SmsProviderFactory $factory): int
    {
        /** @var SmsProvider|null $model */
        $model = SmsProvider::query()
            ->where('driver', SmsProviderDriver::Texcell->value)
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->first();

        $config = $model?->config ?? [];
        $account = trim((string) ($this->option('account') ?: ($config['account'] ?? config('sms.texcell.account'))));
        $password = trim((string) ($this->option('password') ?: ($config['password'] ?? config('sms.texcell.password'))));
        $baseUrl = rtrim(trim((string) ($this->option('base-url') ?: ($config['base_url'] ?? config('sms.texcell.base_url')))), '/');

        $this->info('Texcell teşhis');
        $this->line('DB kayıt: '.($model ? "#{$model->id} {$model->code} (active=".(($model->is_active ?? false) ? 'yes' : 'no').')' : 'yok'));
        $this->line('Base URL: '.$baseUrl);
        $this->line('Account: '.$account);
        $this->line('Password uzunluk: '.strlen($password).' (değer gösterilmez)');
        $this->line('Encryption key: '.(trim((string) ($config['encryption_key'] ?? '')) !== '' ? 'DOLU' : 'boş'));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş. Panelden Texcell kaydına girin veya --account/--password kullanın.');

            return self::FAILURE;
        }

        $url = $baseUrl.'/getbalance?'.http_build_query([
            'account' => $account,
            'password' => $password,
        ]);

        $this->line('İstek: GET '.$baseUrl.'/getbalance?account='.$account.'&password=***');

        try {
            $response = Http::timeout(30)->acceptJson()->get($baseUrl.'/getbalance', [
                'account' => $account,
                'password' => $password,
            ]);
        } catch (\Throwable $e) {
            $this->error('Bağlantı hatası: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('HTTP: '.$response->status());
        $this->line('Yanıt: '.$response->body());
        $this->newLine();

        $data = $response->json();
        $status = is_array($data) ? (int) ($data['status'] ?? -99) : -99;

        if ($status === 0) {
            $this->info('OK — kimlik doğrulandı. Balance: '.($data['balance'] ?? '?').' gift: '.($data['gift'] ?? '?'));

            return self::SUCCESS;
        }

        if ($status === -1) {
            $this->error('Authentication failure (-1)');
            $this->warn('1) Panelde account/password’ü yeniden kaydedin (APP_KEY değiştiyse şifreli config bozulmuş olabilir).');
            $this->warn('2) Encryption Key alanını BOŞ bırakın (Texcell size key vermediyse).');
            $this->warn('3) Sunucu public IP’nizi Texcell whitelist’e ekletin (bazen -1 döner).');
            $this->warn('4) Texcell’e CTU780 hesabının aktif olduğunu doğrulattırın.');
        } elseif ($status === -2) {
            $this->error('IP limited (-2) — sunucu IP whitelist’te değil.');
        } else {
            $this->error("Beklenmeyen status: {$status}");
        }

        // Provider sınıfı üzerinden de dene
        if ($model !== null) {
            $provider = $factory->makeFromModel($model);
            if ($provider instanceof TexcellEimsSmsProvider) {
                $balance = $provider->getBalance();
                $this->line('Provider getBalance(): '.($balance->success ? 'OK '.$balance->balance : 'FAIL '.$balance->errorMessage));
            }
        }

        return self::FAILURE;
    }
}
