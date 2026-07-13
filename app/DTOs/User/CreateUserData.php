<?php

namespace App\DTOs\User;

use App\DTOs\BaseData;
use App\Enums\UserStatus;

/**
 * Kullanıcı oluşturma veri transfer nesnesi.
 */
readonly class CreateUserData extends BaseData
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public UserStatus $status,
        public array $roles,
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $data): static
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            status: UserStatus::from((string) ($data['status'] ?? UserStatus::Active->value)),
            roles: array_values((array) ($data['roles'] ?? [])),
        );
    }
}
