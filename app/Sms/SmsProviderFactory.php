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
        $provider = $this->smsProviderRepository->findDefaultActive();

        if ($provider !== null) {
            return $this->makeFromModel($provider);
        }

        $fallbackCode = config('sms.default_provider', 'texcell');

        return $this->makeByDriver($fallbackCode, []);
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

        return $this->makeByDriver($code, []);
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

        return $this->makeByDriver($driver, $provider->config ?? []);
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

        return new $class($config);
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
