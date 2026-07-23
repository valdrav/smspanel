<?php

namespace App\Console\Commands;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
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

    public function handle(TexcellBalanceSyncService $syncService): int
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
        $this->line('DB kayıt: '.($model ? "#{$model->id} {$model->code}" : 'yok'));
        $this->line('Base URL: '.$baseUrl);
        $this->line('Account: '.$account);
        $this->line('Password uzunluk: '.strlen($password));
        $this->newLine();

        if ($account === '' || $password === '') {
            $this->error('Account/password boş.');

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

        $data = $response->json();
        $status = is_array($data) ? (int) ($data['status'] ?? -99) : -99;

        if ($status === 0) {
            $balance = (float) ($data['balance'] ?? 0) + (float) ($data['gift'] ?? 0);
            $this->info('OK — Balance: '.$balance);

            if ($model !== null) {
                $sync = $syncService->syncProvider($model);
                $this->line($sync->success
                    ? 'SMS hakkı senkronlandı: '.(int) floor($sync->balance)
                    : 'Senkron başarısız: '.$sync->errorMessage);
            }

            return self::SUCCESS;
        }

        if ($status === -1) {
            $this->error('Authentication failure (-1)');
            $this->warn('Whitelist = SUNUCU public IP (curl -4 ifconfig.me), ev/PC IP değil.');
            $this->warn('Panelde account/password yeniden kaydedin; Encryption Key boş; URL :20003');
        } elseif ($status === -2) {
            $this->error('IP limited (-2) — sunucu IP whitelist’te değil.');
        } else {
            $this->error("Status: {$status}");
        }

        return self::FAILURE;
    }
}
