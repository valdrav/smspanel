<?php

namespace App\DTOs\Wallet;

use App\DTOs\BaseData;

/**
 * Bakiye yükleme DTO.
 */
readonly class CreditWalletData extends BaseData
{
    public function __construct(
        public float $amount,
        public string $description,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            amount: (float) $data['amount'],
            description: (string) ($data['description'] ?? 'Bakiye yükleme'),
        );
    }
}
