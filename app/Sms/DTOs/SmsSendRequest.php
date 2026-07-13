<?php

namespace App\Sms\DTOs;

/**
 * SMS gönderim isteği veri transfer nesnesi.
 */
readonly class SmsSendRequest
{
    public function __construct(
        public string $to,
        public string $message,
        public ?string $senderId = null,
        public ?string $referenceId = null,
    ) {}
}
