<?php

namespace App\Enums;

/**
 * Cüzdan işlem tipleri.
 */
enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Refund = 'refund';

    /**
     * Tipin Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::Credit => 'SMS Kredisi Yükleme',
            self::Debit => 'SMS Kullanımı',
            self::Refund => 'SMS İadesi',
        };
    }

    /**
     * Bootstrap badge sınıfını döndürür.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Credit => 'success',
            self::Debit => 'danger',
            self::Refund => 'warning',
        };
    }
}
