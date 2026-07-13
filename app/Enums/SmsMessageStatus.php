<?php

namespace App\Enums;

/**
 * SMS mesaj durumları.
 */
enum SmsMessageStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    /**
     * Durumun Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Queued => 'Kuyrukta',
            self::Sent => 'Gönderildi',
            self::Delivered => 'Teslim Edildi',
            self::Failed => 'Başarısız',
        };
    }

    /**
     * Bootstrap badge sınıfını döndürür.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Queued => 'info',
            self::Sent => 'primary',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }
}
