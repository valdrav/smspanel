<?php

namespace App\DTOs\Auth;

use App\DTOs\BaseData;

/**
 * Giriş işlemi veri transfer nesnesi.
 */
readonly class LoginData extends BaseData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            email: (string) $data['email'],
            password: (string) $data['password'],
            remember: (bool) ($data['remember'] ?? false),
        );
    }
}
