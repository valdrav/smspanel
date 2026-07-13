<?php

namespace App\DTOs\User;

use App\DTOs\BaseData;
use App\Enums\UserStatus;

/**
 * Kullanıcı güncelleme veri transfer nesnesi.
 */
readonly class UpdateUserData extends BaseData
{
    /**
     * @param  list<string>|null  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public UserStatus $status,
        public ?string $password,
        public ?array $roles,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            status: UserStatus::from((string) $data['status']),
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            roles: isset($data['roles']) ? array_values((array) $data['roles']) : null,
        );
    }
}
