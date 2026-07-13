<?php

namespace App\Enums;

enum CampaignRecipientStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Bekliyor',
            self::Queued => 'Kuyrukta',
            self::Sent => 'Gönderildi',
            self::Failed => 'Başarısız',
        };
    }
}
