<?php

namespace App\Sms\DTOs;

/**
 * SMS gönderim sonucu veri transfer nesnesi.
 */
readonly class SmsSendResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $status = null,
        public ?string $errorMessage = null,
        public ?float $cost = null,
    ) {}
}
