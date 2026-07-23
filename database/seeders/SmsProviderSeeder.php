<?php

namespace Database\Seeders;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use App\Services\Sms\EnsureTexcellProvider;
use Illuminate\Database\Seeder;

/**
 * Varsayılan SMS sağlayıcı: yalnızca Texcell (config’den).
 */
class SmsProviderSeeder extends Seeder
{
    public function run(): void
    {
        $account = trim((string) config('sms.texcell.account', ''));
        $password = (string) config('sms.texcell.password', '');
        $texcellConfigured = $account !== '' && $password !== '';

        if ($texcellConfigured) {
            app(EnsureTexcellProvider::class)->ensure();

            return;
        }

        // Test ortamı: Texcell kimliği yoksa mock varsayılan kalsın.
        SmsProvider::updateOrCreate(
            ['code' => 'mock'],
            [
                'name' => 'Mock Sağlayıcı (Test)',
                'driver' => SmsProviderDriver::Mock->value,
                'config' => [],
                'is_active' => true,
                'is_default' => true,
                'priority' => 100,
            ]
        );

        SmsProvider::updateOrCreate(
            ['code' => 'texcell'],
            [
                'name' => 'Texcell EIMS',
                'driver' => SmsProviderDriver::Texcell->value,
                'config' => [
                    'account' => '',
                    'password' => '',
                    'base_url' => (string) config('sms.texcell.base_url', 'http://38.150.64.36:20003'),
                    'sender' => '',
                    'encryption_key' => '',
                ],
                'is_active' => false,
                'is_default' => false,
                'priority' => 1,
            ]
        );

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
