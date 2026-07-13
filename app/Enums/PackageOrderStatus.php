<?php

namespace App\Enums;

enum PackageOrderStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Approved => 'Onaylandı',
            self::Rejected => 'Reddedildi',
            self::Cancelled => 'İptal',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'secondary',
        };
    }
}
