<?php

namespace App\Sms\Providers;

use App\Sms\Contracts\SmsProviderInterface;
use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;

/**
 * Yapılandırılabilir SMS sağlayıcı temel sınıfı.
 */
abstract class AbstractSmsProvider implements SmsProviderInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = []) {}

    /**
     * Yapılandırma değerini döndürür.
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function sendBulk(array $requests): array
    {
        return array_map(fn (SmsSendRequest $request): SmsSendResult => $this->send($request), $requests);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getName(): string;

    /**
     * {@inheritDoc}
     */
    abstract public function send(SmsSendRequest $request): SmsSendResult;

    /**
     * {@inheritDoc}
     */
    abstract public function getBalance(): SmsBalanceResult;

    /**
     * {@inheritDoc}
     */
    abstract public function getDeliveryStatus(string $messageId): SmsSendResult;
}
