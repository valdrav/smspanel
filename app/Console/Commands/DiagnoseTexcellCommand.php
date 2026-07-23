<?php

namespace App\Console\Commands;

use App\Enums\SmsProviderDriver;
use App\Services\Sms\EnsureTexcellProvider;
use App\Services\Sms\TexcellBalanceSyncService;
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

    public function handle(EnsureTexcellProvider $ensure, TexcellBalanceSyncService $syncService): int
    {
        $model = $ensure->ensure();
        $resolved = $ensure->resolvedConfig();

        $account = trim((string) ($this->option('account') ?: $resolved['account']));
        $password = trim((string) ($this->option('password') ?: $resolved['password']));
        $baseUrl = rtrim(trim((string) ($this->option('base-url') ?: $resolved['base_url'])), '/');

        $this->info('Texcell teşhis');
        $this->line('DB kayıt: #'.$model->id.' code='.$model->code.' driver='.$model->driverValue());
        $this->line('Aktif: '.($model->is_active ? 'evet' : 'hayır').' | Varsayılan: '.($model->is_default ? 'evet' : 'hayır'));
        $this->line('Base URL: '.$baseUrl);
        $this->line('Account: '.($account !== '' ? $account : '(BOŞ)'));
        $this->line('Password uzunluk: '.strlen($password).($password === '' ? ' (BOŞ!)' : ''));
        $this->line('Encryption key: '.(trim((string) ($resolved['encryption_key'] ?? '')) !== '' ? 'DOLU' : 'boş'));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş.');

            return self::FAILURE;
        }

        if ($model->driverValue() !== SmsProviderDriver::Texcell->value) {
            $this->error('Texcell kaydı oluşturulamadı.');

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
            $this->error('Anlamı: Texcell hesabı/şifreyi veya IP’yi reddetti (Authentication failure).');
            $this->warn('1) Whitelist’e SUNUCU public IP ekleyin (ev/PC IP değil): sunucuda `curl -4 ifconfig.me`');
            $this->warn('2) Encryption Key boş olmalı');
            $this->warn('3) Base URL: http://38.150.64.36:20003');
        } elseif ($status === -2) {
            $this->error('Anlamı: Sunucu IP whitelist’te değil (-2).');
        } else {
            $this->error("Beklenmeyen status: {$status}");
        }

        return self::FAILURE;
    }
}
