<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingCustomer = 'waiting_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Açık',
            self::InProgress => 'İşlemde',
            self::WaitingCustomer => 'Müşteri Yanıtı Bekleniyor',
            self::Resolved => 'Çözüldü',
            self::Closed => 'Kapatıldı',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'primary',
            self::WaitingCustomer => 'warning',
            self::Resolved => 'success',
            self::Closed => 'secondary',
        };
    }
}
