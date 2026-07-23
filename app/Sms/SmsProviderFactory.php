<?php

namespace App\Sms;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Sms\Contracts\SmsProviderInterface;
use App\Sms\Providers\MockSmsProvider;
use App\Sms\Providers\NetgsmSmsProvider;
use InvalidArgumentException;

/**
 * SMS sağlayıcı fabrika ve çözümleyici sınıfı.
 */
class SmsProviderFactory
{
    /**
     * @var array<string, class-string<SmsProviderInterface>>
     */
    private array $drivers;

    public function __construct(
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
    ) {
        $this->drivers = config('sms.drivers', [
            SmsProviderDriver::Mock->value => MockSmsProvider::class,
            SmsProviderDriver::Netgsm->value => NetgsmSmsProvider::class,
            SmsProviderDriver::Texcell->value => \App\Sms\Providers\TexcellEimsSmsProvider::class,
        ]);
    }

    /**
     * Varsayılan aktif sağlayıcıyı döndürür.
     */
    public function resolveDefault(): SmsProviderInterface
    {
        $preferred = (string) config('sms.default_provider', SmsProviderDriver::Texcell->value);

        // Üretimde SMS_DEFAULT_PROVIDER=texcell iken mock DB varsayılanı olsa bile Texcell kullanılır.
        if ($preferred === SmsProviderDriver::Texcell->value) {
            $texcell = $this->smsProviderRepository->findByCode('texcell');

            if ($texcell !== null && $texcell->is_active) {
                return $this->makeFromModel($texcell);
            }

            return $this->makeByDriver(SmsProviderDriver::Texcell->value, $this->texcellConfig([]));
        }

        $provider = $this->smsProviderRepository->findDefaultActive();

        if ($provider !== null) {
            return $this->makeFromModel($provider);
        }

        return $this->makeByDriver($preferred, $preferred === SmsProviderDriver::Texcell->value ? $this->texcellConfig([]) : []);
    }

    /**
     * Kod ile sağlayıcı döndürür.
     */
    public function resolveByCode(string $code): SmsProviderInterface
    {
        $provider = $this->smsProviderRepository->findByCode($code);

        if ($provider !== null) {
            return $this->makeFromModel($provider);
        }

        return $this->makeByDriver(
            $code,
            $code === SmsProviderDriver::Texcell->value ? $this->texcellConfig([]) : []
        );
    }

    /**
     * Modelden sağlayıcı örneği oluşturur.
     */
    public function makeFromModel(SmsProvider $provider): SmsProviderInterface
    {
        $driver = $provider->driverValue();

        if ($driver === '' || $provider->driver === null) {
            throw new InvalidArgumentException("SMS sürücüsü bulunamadı veya desteklenmiyor: {$provider->code}");
        }

        $config = $provider->config ?? [];

        if ($driver === SmsProviderDriver::Texcell->value) {
            $config = $this->texcellConfig($config);
        }

        return $this->makeByDriver($driver, $config);
    }

    /**
     * Sürücü adına göre sağlayıcı oluşturur.
     *
     * @param  array<string, mixed>  $config
     */
    public function makeByDriver(string $driver, array $config): SmsProviderInterface
    {
        if (! isset($this->drivers[$driver])) {
            throw new InvalidArgumentException("SMS sürücüsü bulunamadı: {$driver}");
        }

        $class = $this->drivers[$driver];

        if ($driver === SmsProviderDriver::Texcell->value) {
            $config = $this->texcellConfig($config);
        }

        return new $class($config);
    }

    /**
     * Config/env Texcell kimliğini DB boş alanlarının üzerine yazar.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function texcellConfig(array $config): array
    {
        foreach (['account', 'password', 'base_url', 'sender', 'encryption_key'] as $key) {
            $fromConfig = trim((string) config("sms.texcell.{$key}", ''));
            $fromDb = trim((string) ($config[$key] ?? ''));

            if ($fromConfig !== '') {
                $config[$key] = $fromConfig;
            } elseif ($fromDb !== '') {
                $config[$key] = $fromDb;
            } elseif ($key === 'base_url') {
                $config[$key] = 'http://38.150.64.36:20003';
            } else {
                $config[$key] = $fromDb;
            }
        }

        return $config;
    }

    /**
     * Kayıtlı sürücü listesini döndürür.
     *
     * @return list<string>
     */
    public function getRegisteredDrivers(): array
    {
        return array_keys($this->drivers);
    }
}
