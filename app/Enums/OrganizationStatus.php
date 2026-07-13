<?php

namespace App\Enums;

/**
 * Organizasyon durumları.
 */
enum OrganizationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    /**
     * Durumun Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Pasif',
            self::Suspended => 'Askıya Alındı',
        };
    }

    /**
     * Bootstrap badge sınıfını döndürür.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            self::Suspended => 'danger',
        };
    }
}
