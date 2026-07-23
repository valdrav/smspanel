<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Varsayılan yönetici kullanıcılarını oluşturur.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Seed işlemini çalıştırır.
     */
    public function run(): void
    {
        $admins = [
            [
                'email' => 'admin@allwhite.com.tr',
                'name' => 'Süper Yönetici',
                'password' => 'Allwhite123!',
                'phone' => '5550000001',
            ],
            [
                'email' => 'admin@smspanel.local',
                'name' => 'Süper Yönetici',
                'password' => 'Admin123!',
                'phone' => '5550000000',
            ],
        ];

        foreach ($admins as $data) {
            $admin = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'status' => UserStatus::Active->value,
                    'sms_balance' => 0,
                    'sms_sender_id' => null,
                    'email_verified_at' => now(),
                ]
            );

            $admin->syncRoles([RoleName::SuperAdmin->value]);
        }
    }
}
