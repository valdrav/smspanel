<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Contact;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Admin/müşteri örnek kullanıcılar, rehber ve şablon verileri.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::updateOrCreate(
            ['email' => 'yonetici@smspanel.local'],
            [
                'name' => 'Demo Yönetici',
                'phone' => '5553334455',
                'password' => Hash::make('Yonetici123!'),
                'status' => UserStatus::Active->value,
                'sms_balance' => 2500,
                'sms_sender_id' => 'PANEL',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles([RoleName::Admin->value]);

        $owner = User::where('email', 'musteri@smspanel.local')->first();

        if ($owner === null) {
            $owner = User::updateOrCreate(
                ['email' => 'musteri@smspanel.local'],
                [
                    'name' => 'Demo Müşteri',
                    'phone' => '5552223344',
                    'password' => Hash::make('Musteri123!'),
                    'status' => UserStatus::Active->value,
                    'sms_balance' => 800,
                    'sms_sender_id' => 'MUSTERI',
                    'email_verified_at' => now(),
                ]
            );
            $owner->syncRoles([RoleName::Customer->value]);
        }

        $contacts = [
            ['name' => 'Ayşe Yılmaz', 'phone' => '5551112233', 'email' => 'ayse@ornek.com'],
            ['name' => 'Mehmet Demir', 'phone' => '5554445566', 'email' => 'mehmet@ornek.com'],
            ['name' => 'Zeynep Kara', 'phone' => '5557778899', 'email' => 'zeynep@ornek.com'],
            ['name' => 'Ali Can', 'phone' => '5556667788', 'email' => null],
            ['name' => 'Elif Şahin', 'phone' => '5559990011', 'email' => 'elif@ornek.com'],
            ['name' => 'Burak Öz', 'phone' => '5552221100', 'email' => null],
            ['name' => 'Selin Akar', 'phone' => '5553332211', 'email' => 'selin@ornek.com'],
            ['name' => 'Emre Kılıç', 'phone' => '5558887766', 'email' => 'emre@ornek.com'],
        ];

        foreach ([$owner, $adminUser] as $user) {
            foreach ($contacts as $index => $contact) {
                Contact::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'phone' => $contact['phone'],
                    ],
                    [
                        'name' => $contact['name'],
                        'email' => $contact['email'],
                        'notes' => 'Örnek rehber kaydı #'.($index + 1),
                        'is_active' => true,
                    ]
                );
            }

            $templates = [
                [
                    'name' => 'Hoş Geldiniz',
                    'body' => 'Merhaba, aramıza hoş geldiniz! Bilgilendirmelerimiz SMS olarak iletilecektir.',
                ],
                [
                    'name' => 'Kampanya Duyurusu',
                    'body' => 'Özel kampanyamız başladı! Detaylar için bizi arayın. İyi günler dileriz.',
                ],
                [
                    'name' => 'Randevu Hatırlatma',
                    'body' => 'Sayın müşterimiz, yarınki randevunuzu hatırlatmak isteriz. Görüşmek üzere.',
                ],
            ];

            foreach ($templates as $template) {
                SmsTemplate::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $template['name'],
                    ],
                    [
                        'body' => $template['body'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
