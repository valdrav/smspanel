<?php

namespace Database\Seeders;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Database\Seeder;

/**
 * Varsayılan SMS sağlayıcı kayıtlarını oluşturur.
 */
class SmsProviderSeeder extends Seeder
{
    public function run(): void
    {
        $texcellAccount = trim((string) config('sms.texcell.account', ''));
        $texcellPassword = (string) config('sms.texcell.password', '');
        $texcellEnabled = $texcellAccount !== '' && $texcellPassword !== '';

        SmsProvider::firstOrCreate(
            ['code' => 'mock'],
            [
                'name' => 'Mock Sağlayıcı (Test)',
                'driver' => SmsProviderDriver::Mock->value,
                'config' => [],
                'is_active' => ! $texcellEnabled,
                'is_default' => false,
                'priority' => 100,
            ]
        );

        /** @var SmsProvider|null $existing */
        $existing = SmsProvider::query()->where('code', 'texcell')->first();
        $existingConfig = $existing?->config ?? [];

        $config = [
            'account' => $texcellEnabled ? $texcellAccount : (string) ($existingConfig['account'] ?? ''),
            'password' => $texcellEnabled ? $texcellPassword : (string) ($existingConfig['password'] ?? ''),
            'base_url' => (string) config('sms.texcell.base_url'),
            'sender' => (string) ($existingConfig['sender'] ?? config('sms.texcell.sender', '')),
            'encryption_key' => $texcellEnabled
                ? (string) config('sms.texcell.encryption_key', '')
                : (string) ($existingConfig['encryption_key'] ?? config('sms.texcell.encryption_key', '')),
        ];

        $texcell = SmsProvider::updateOrCreate(
            ['code' => 'texcell'],
            [
                'name' => 'Texcell EIMS',
                'driver' => SmsProviderDriver::Texcell->value,
                'config' => $config,
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
            ]
        );

        SmsProvider::query()
            ->whereKeyNot($texcell->id)
            ->update(['is_default' => false]);

        // Eski EasySendSMS kaydı varsa pasifleştir.
        SmsProvider::query()
            ->where(function ($query): void {
                $query->where('code', 'easysendsms')
                    ->orWhere('driver', 'easysendsms');
            })
            ->update([
                'is_active' => false,
                'is_default' => false,
            ]);
    }
}
