<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Support\Str;

/**
 * Geliştirme ve test ortamı için mock SMS sağlayıcısı.
 */
class MockSmsProvider extends AbstractSmsProvider
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'mock';
    }

    /**
     * {@inheritDoc}
     */
    public function send(SmsSendRequest $request): SmsSendResult
    {
        return new SmsSendResult(
            success: true,
            messageId: 'MOCK-'.Str::uuid()->toString(),
            status: 'delivered',
            cost: 0.05,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(): SmsBalanceResult
    {
        return new SmsBalanceResult(
            success: true,
            balance: 10000.0,
            currency: 'TRY',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDeliveryStatus(string $messageId): SmsSendResult
    {
        return new SmsSendResult(
            success: true,
            messageId: $messageId,
            status: 'delivered',
        );
    }
}
