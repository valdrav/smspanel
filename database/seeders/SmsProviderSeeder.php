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
        SmsProvider::firstOrCreate(
            ['code' => 'mock'],
            [
                'name' => 'Mock Sağlayıcı (Test)',
                'driver' => SmsProviderDriver::Mock->value,
                'config' => [],
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
            ]
        );
    }
}
