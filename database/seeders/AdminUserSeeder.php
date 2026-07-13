<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Varsayılan yönetici kullanıcısını oluşturur.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Seed işlemini çalıştırır.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@smspanel.local'],
            [
                'name' => 'Süper Yönetici',
                'phone' => '5550000000',
                'password' => Hash::make('Admin123!'),
                'status' => UserStatus::Active->value,
                'sms_balance' => 10000.0000,
                'sms_sender_id' => 'SMSPANEL',
                'email_verified_at' => now(),
            ]
        );

        if ((float) $admin->sms_balance <= 0) {
            $admin->update(['sms_balance' => 10000.0000]);
        }

        $admin->syncRoles([RoleName::SuperAdmin->value]);
    }
}
