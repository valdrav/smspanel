<?php

namespace App\DTOs\UserSenderNumber;

use App\DTOs\BaseData;

readonly class UpdateUserSenderNumberData extends BaseData
{
    public function __construct(
        public string $senderId,
        public ?string $label,
        public bool $isDefault,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            senderId: strtoupper((string) $data['sender_id']),
            label: isset($data['label']) && $data['label'] !== '' ? (string) $data['label'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
