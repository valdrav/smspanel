<?php

namespace App\DTOs\Sms;

use App\DTOs\BaseData;

/**
 * Tekil SMS gönderim veri transfer nesnesi.
 */
readonly class SendSmsData extends BaseData
{
    public function __construct(
        public string $recipient,
        public string $message,
        public ?string $senderId = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            recipient: (string) $data['recipient'],
            message: (string) $data['message'],
            senderId: isset($data['sender_id']) && $data['sender_id'] !== '' ? (string) $data['sender_id'] : null,
        );
    }
}
