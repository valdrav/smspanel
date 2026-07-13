<?php

namespace App\Enums;

/**
 * Sistem rol tanımları.
 */
enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Customer = 'customer';

    /**
     * Rolün Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Süper Yönetici',
            self::Admin => 'Yönetici',
            self::Customer => 'Müşteri',
        };
    }

    public static function labelFor(?string $name): string
    {
        if ($name === null) {
            return '—';
        }

        return self::tryFrom($name)?->label() ?? $name;
    }
}
