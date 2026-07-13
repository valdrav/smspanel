<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Beklemede',
            self::Processing => 'Gönderiliyor',
            self::Completed => 'Tamamlandı',
            self::Cancelled => 'İptal',
            self::Failed => 'Başarısız',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'warning',
            self::Failed => 'danger',
        };
    }
}
