<?php

namespace Database\Seeders;

use App\Services\Contracts\SettingServiceInterface;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        /** @var SettingServiceInterface $settings */
        $settings = app(SettingServiceInterface::class);

        $settings->setMany([
            'app_name' => 'SMS Panel',
            'app_tagline' => 'Kurumsal SMS Yönetim Platformu',
            'support_email' => 'destek@smspanel.local',
            'primary_color' => '#6366f1',
            'accent_color' => '#22d3ee',
        ], 'general');

        $settings->setMany([
            'login_welcome' => 'Hoş geldiniz',
            'footer_text' => '© '.date('Y').' SMS Panel',
        ], 'branding');
    }
}
