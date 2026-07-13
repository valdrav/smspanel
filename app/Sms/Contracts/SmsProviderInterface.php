<?php

namespace App\Sms\Contracts;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;

/**
 * SMS sağlayıcı sözleşmesi.
 *
 * Tüm SMS API entegrasyonları bu arayüzü implement etmelidir.
 */
interface SmsProviderInterface
{
    /**
     * Sağlayıcının benzersiz kod adını döndürür.
     */
    public function getName(): string;

    /**
     * Tekil SMS gönderir.
     */
    public function send(SmsSendRequest $request): SmsSendResult;

    /**
     * Toplu SMS gönderir.
     *
     * @param  list<SmsSendRequest>  $requests
     * @return list<SmsSendResult>
     */
    public function sendBulk(array $requests): array;

    /**
     * Sağlayıcı bakiyesini sorgular.
     */
    public function getBalance(): SmsBalanceResult;

    /**
     * Gönderim durumunu sorgular.
     */
    public function getDeliveryStatus(string $messageId): SmsSendResult;
}
