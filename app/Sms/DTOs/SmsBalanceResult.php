<?php

namespace App\Sms\DTOs;

/**
 * SMS sağlayıcı bakiye sorgu sonucu.
 */
readonly class SmsBalanceResult
{
    public function __construct(
        public bool $success,
        public float $balance = 0.0,
        public string $currency = 'TRY',
        public ?string $errorMessage = null,
    ) {}
}
