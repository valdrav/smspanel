<?php

use App\Console\Commands\PollTexcellDeliveryReportsCommand;
use Database\Seeders\SmsPackageSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('smspanel:seed-packages', function () {
    $this->info('Migration kontrol ediliyor...');
    Artisan::call('migrate', ['--force' => true]);
    $this->line(trim(Artisan::output()));

    $this->info('Örnek paketler yükleniyor...');
    Artisan::call('db:seed', ['--class' => SmsPackageSeeder::class, '--force' => true]);
    $this->info('Tamam: örnek SMS paketleri hazır.');
})->purpose('Paket migration + örnek paketleri yükler');

Schedule::command(PollTexcellDeliveryReportsCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();
