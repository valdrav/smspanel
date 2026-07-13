<?php

namespace App\Enums;

enum TicketCategory: string
{
    case General = 'general';
    case Billing = 'billing';
    case Technical = 'technical';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Genel',
            self::Billing => 'Fatura / Ödeme',
            self::Technical => 'Teknik',
            self::Package => 'Paket / Bakiye',
        };
    }
}
