<?php

namespace App\DTOs\Sms;

use App\DTOs\BaseData;

/**
 * Toplu SMS gönderim veri transfer nesnesi.
 */
readonly class SendBulkSmsData extends BaseData
{
    /**
     * @param  list<string>  $recipients
     */
    public function __construct(
        public array $recipients,
        public string $message,
        public ?string $senderId = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        $recipients = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', (string) ($data['recipients'] ?? '')) ?: []
        )));

        return new self(
            recipients: $recipients,
            message: (string) $data['message'],
            senderId: isset($data['sender_id']) && $data['sender_id'] !== '' ? (string) $data['sender_id'] : null,
        );
    }
}
