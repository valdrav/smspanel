<?php

namespace App\Console\Commands;

use App\Services\Sms\EnsureTexcellProvider;
use App\Services\Sms\TexcellBalanceSyncService;
use Illuminate\Console\Command;

/**
 * Texcell’i tek varsayılan sağlayıcı yapar (config → DB) ve bakiyeyi çeker.
 *
 * Yoksa: php artisan sms:texcell-diagnose (aynı kurulumu yapar)
 */
class InstallTexcellCommand extends Command
{
    protected $signature = 'sms:texcell-install
                            {--no-sync : Bakiye senkronunu atla}
                            {--diagnose : Kurulumdan sonra teşhis de çalıştır}';

    protected $description = 'Texcell hesabını DB’ye yazar, mock’u Texcell’e çevirir, varsayılan yapar';

    public function handle(EnsureTexcellProvider $ensure, TexcellBalanceSyncService $sync): int
    {
        $provider = $ensure->ensure();
        $config = $provider->config ?? [];

        $this->info('Texcell kuruldu / güncellendi');
        $this->line('DB kayıt: #'.$provider->id.' code='.$provider->code.' driver='.$provider->driverValue());
        $this->line('Account: '.($config['account'] ?? ''));
        $this->line('Base URL: '.($config['base_url'] ?? ''));
        $this->line('Aktif + varsayılan: evet (diğer sağlayıcılar pasif)');
        $this->newLine();

        if ($this->option('diagnose')) {
            return $this->call('sms:texcell-diagnose', ['--skip-ensure' => true]);
        }

        if ($this->option('no-sync')) {
            return self::SUCCESS;
        }

        $result = $sync->syncProvider($provider);

        if ($result->success) {
            $this->info('Texcell bakiyesi panel SMS hakkına yazıldı: '.(int) floor((float) $result->balance));

            return self::SUCCESS;
        }

        $this->error('Bakiye alınamadı: '.$result->errorMessage);
        $this->warn('Kurulum tamam. Auth/IP için: php artisan sms:texcell-diagnose');

        return self::FAILURE;
    }
}
