<?php

namespace App\DTOs\Organization;

use App\DTOs\BaseData;
use App\Enums\OrganizationStatus;

/**
 * Organizasyon oluşturma DTO.
 */
readonly class CreateOrganizationData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $taxNumber,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public OrganizationStatus $status,
        public ?string $smsSenderId,
        public ?string $notes,
        public float $initialBalance = 0,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: (string) $data['name'],
            taxNumber: isset($data['tax_number']) ? (string) $data['tax_number'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            status: OrganizationStatus::from((string) ($data['status'] ?? OrganizationStatus::Active->value)),
            smsSenderId: isset($data['sms_sender_id']) && $data['sms_sender_id'] !== '' ? (string) $data['sms_sender_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            initialBalance: (float) ($data['initial_balance'] ?? 0),
        );
    }
}
