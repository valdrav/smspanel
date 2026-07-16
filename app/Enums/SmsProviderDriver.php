<?php

namespace App\Enums;

/**
 * SMS sağlayıcı sürücü tipleri.
 */
enum SmsProviderDriver: string
{
    case Mock = 'mock';
    case Netgsm = 'netgsm';
    case IletiMerkezi = 'iletimerkezi';
    case EasySendSms = 'easysendsms';

    /**
     * Sürücünün Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::Mock => 'Mock (Test)',
            self::Netgsm => 'Netgsm',
            self::IletiMerkezi => 'İleti Merkezi',
            self::EasySendSms => 'EasySendSMS',
        };
    }
}
