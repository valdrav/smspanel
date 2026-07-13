<?php

namespace App\Services\Contracts;

interface SettingServiceInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, string $group = 'general'): void;

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings, string $group = 'general'): void;

    /**
     * @return array<string, mixed>
     */
    public function allGrouped(): array;

    public function applyBranding(): void;

    public function clearCache(): void;
}
