<?php

namespace App\DTOs\SmsProvider;

use App\DTOs\BaseData;
use App\Enums\SmsProviderDriver;

/**
 * SMS sağlayıcı güncelleme DTO.
 */
readonly class UpdateSmsProviderData extends BaseData
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $name,
        public SmsProviderDriver $driver,
        public array $config,
        public bool $isActive,
        public bool $isDefault,
        public int $priority,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            name: (string) $data['name'],
            driver: SmsProviderDriver::from((string) $data['driver']),
            config: (array) ($data['config'] ?? []),
            isActive: (bool) ($data['is_active'] ?? true),
            isDefault: (bool) ($data['is_default'] ?? false),
            priority: (int) ($data['priority'] ?? 100),
        );
    }
}
