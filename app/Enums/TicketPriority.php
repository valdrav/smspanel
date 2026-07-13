<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Düşük',
            self::Normal => 'Normal',
            self::High => 'Yüksek',
            self::Urgent => 'Acil',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'secondary',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
