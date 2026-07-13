<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSenderNumber;
use Illuminate\Database\Seeder;

class UserSenderNumberSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@smspanel.local')->first();
        $customer = User::where('email', 'musteri@smspanel.local')->first();

        if ($admin) {
            UserSenderNumber::firstOrCreate(
                ['user_id' => $admin->id, 'sender_id' => 'SMSPANEL'],
                ['label' => 'Ana hat', 'is_default' => true, 'is_active' => true],
            );
        }

        if ($customer) {
            UserSenderNumber::firstOrCreate(
                ['user_id' => $customer->id, 'sender_id' => 'DEMO'],
                ['label' => 'Demo başlık', 'is_default' => true, 'is_active' => true],
            );
        }
    }
}
