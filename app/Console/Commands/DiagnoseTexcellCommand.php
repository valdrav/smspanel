<?php

namespace App\Console\Commands;

use App\Enums\SmsProviderDriver;
use App\Services\Sms\EnsureTexcellProvider;
use App\Services\Sms\TexcellBalanceSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Texcell kurulumu + kimlik/IP teşhisi (SMS göndermez).
 *
 * Not: sms:texcell-install aynı işi yapar; sunucuda yeni komut yoksa diagnose yeter.
 */
class DiagnoseTexcellCommand extends Command
{
    protected $signature = 'sms:texcell-diagnose
                            {--account= : Geçici account (DB yerine)}
                            {--password= : Geçici password (DB yerine)}
                            {--base-url= : Geçici base URL}
                            {--skip-ensure : DB düzeltmesini atla}';

    protected $description = 'Texcell’i DB’ye yazar, getbalance test eder, sunucu IP gösterir';

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
        $publicIp = $this->detectPublicIp();

        $this->newLine();
        $this->info('Texcell teşhis');
        $this->line('DB kayıt: #'.$model->id.' code='.$model->code.' driver='.$model->driverValue());
        $this->line('Aktif: '.($model->is_active ? 'evet' : 'hayır').' | Varsayılan: '.($model->is_default ? 'evet' : 'hayır'));
        $this->line('Base URL: '.$baseUrl);
        $this->line('Account: '.($account !== '' ? $account : '(BOŞ)'));
        $this->line('Password uzunluk: '.strlen($password).($password === '' ? ' (BOŞ!)' : ''));
        $this->line('Encryption key: '.(trim((string) ($resolved['encryption_key'] ?? '')) !== '' ? 'DOLU' : 'boş'));
        $this->line('Sunucu public IP (whitelist’e bunu verin): '.($publicIp ?? '(alınamadı — curl -4 ifconfig.me)'));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş.');

            return self::FAILURE;
        }

        if ($model->driverValue() !== SmsProviderDriver::Texcell->value || $model->code !== 'texcell') {
            $this->error('DB hâlâ Texcell değil. Kod güncel mi? php artisan optimize:clear');

            return self::FAILURE;
        }

        $this->line('GET '.$baseUrl.'/getbalance?account='.$account.'&password=***');

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
            $balance = (float) ($data['balance'] ?? 0) + (float) ($data['gift'] ?? 0);
            $this->info('OK — Texcell bakiyesi: '.$balance);

            $sync = $syncService->syncProvider($model);
            $this->line($sync->success
                ? 'Panel SMS hakkı bu bakiyeye çekildi: '.(int) floor($sync->balance)
                : 'Senkron uyarısı: '.$sync->errorMessage);

            return self::SUCCESS;
        }

        if ($status === -1) {
            $this->error('Authentication failure (-1): hesap/şifre veya IP reddedildi.');
            $this->warn('Texcell’e whitelist için verin: '.($publicIp ?? 'sunucu public IP (ifconfig.me)'));
            $this->warn('Ev/PC IP’si işe yaramaz — panelin çalıştığı sunucu IP’si gerekir.');
            $this->warn('Encryption Key boş olmalı | URL :20003 | Account CTU780');
        } elseif ($status === -2) {
            $this->error('IP whitelist’te değil (-2). Whitelist IP: '.($publicIp ?? '?'));
        } else {
            $this->error("Beklenmeyen status: {$status}");
        }

        return self::FAILURE;
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
                // sonraki kaynağı dene
            }
        }

        return null;
    }
}
