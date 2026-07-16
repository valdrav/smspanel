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
        $apiKey = (string) config('sms.easysendsms.api_key', '');
        $easySendSmsEnabled = $apiKey !== '';

        SmsProvider::firstOrCreate(
            ['code' => 'mock'],
            [
                'name' => 'Mock Sağlayıcı (Test)',
                'driver' => SmsProviderDriver::Mock->value,
                'config' => [],
                'is_active' => true,
                'is_default' => ! $easySendSmsEnabled,
                'priority' => 100,
            ]
        );

        $easySendSms = SmsProvider::firstOrCreate(
            ['code' => 'easysendsms'],
            [
                'name' => 'EasySendSMS',
                'driver' => SmsProviderDriver::EasySendSms->value,
                'config' => [
                    'api_key' => $apiKey,
                    'sender_id' => (string) config('sms.easysendsms.sender_id', 'SMSPANEL'),
                    'base_url' => (string) config('sms.easysendsms.base_url'),
                ],
                'is_active' => $easySendSmsEnabled,
                'is_default' => $easySendSmsEnabled,
                'priority' => 1,
            ]
        );

        // Panelden girilmiş anahtarı boş env değeriyle ezme.
        if ($easySendSmsEnabled) {
            SmsProvider::query()
                ->whereKeyNot($easySendSms->id)
                ->update(['is_default' => false]);

            $easySendSms->update([
                'config' => [
                    'api_key' => $apiKey,
                    'sender_id' => (string) config('sms.easysendsms.sender_id', 'SMSPANEL'),
                    'base_url' => (string) config('sms.easysendsms.base_url'),
                ],
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
            ]);
        }
    }
}
