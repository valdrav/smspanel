<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Uygulama seed işlemlerini çalıştırır.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SettingSeeder::class,
            SmsProviderSeeder::class,
            AdminUserSeeder::class,
            OrganizationSeeder::class,
            UserSenderNumberSeeder::class,
        ]);
    }
}
