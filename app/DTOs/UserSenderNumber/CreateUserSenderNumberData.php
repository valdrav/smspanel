<?php

namespace App\DTOs\UserSenderNumber;

use App\DTOs\BaseData;

readonly class CreateUserSenderNumberData extends BaseData
{
    public function __construct(
        public int $userId,
        public string $senderId,
        public ?string $label,
        public bool $isDefault,
        public bool $isActive,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            userId: (int) $data['user_id'],
            senderId: strtoupper((string) $data['sender_id']),
            label: isset($data['label']) && $data['label'] !== '' ? (string) $data['label'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
