<?php

namespace App\Sms\Support;

/**
 * SMS segment ve kredi (adet) hesaplayıcı.
 *
 * Türkçe karakterler Unicode (UCS-2) segment kurallarına göre hesaplanır.
 */
class SmsSegmentCalculator
{
    /**
     * GSM 7-bit karakter seti (temel).
     */
    private const GSM_BASIC_CHARS = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /**
     * GSM genişletilmiş karakterler (2 birim sayılır).
     */
    private const GSM_EXTENDED_CHARS = "^{}\\[~]|€";

    /**
     * Mesajın segment sayısını hesaplar.
     */
    public function calculateSegments(string $message): int
    {
        if ($message === '') {
            return 1;
        }

        if ($this->requiresUnicodeEncoding($message)) {
            $length = mb_strlen($message, 'UTF-8');

            return $length <= 70 ? 1 : (int) ceil($length / 67);
        }

        $length = $this->calculateGsmLength($message);

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    /**
     * Segment sayısı kadar SMS hakkı tüketimini döndürür (1 segment = 1 adet).
     */
    public function calculateCreditsUsed(string $message): int
    {
        return $this->calculateSegments($message);
    }

    /**
     * @deprecated {@see calculateCreditsUsed()} — artık para birimi değil, segment/adet bazlı
     */
    public function calculateCost(string $message, float $costPerSegment = 1): float
    {
        return (float) $this->calculateSegments($message);
    }

    /**
     * Mesajın Unicode gerektirip gerektirmediğini kontrol eder.
     */
    public function requiresUnicodeEncoding(string $message): bool
    {
        $length = mb_strlen($message, 'UTF-8');

        for ($index = 0; $index < $length; $index++) {
            $char = mb_substr($message, $index, 1, 'UTF-8');

            if (str_contains(self::GSM_BASIC_CHARS, $char)) {
                continue;
            }

            if (str_contains(self::GSM_EXTENDED_CHARS, $char)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * GSM uzunluğunu hesaplar.
     */
    private function calculateGsmLength(string $message): int
    {
        $length = 0;
        $messageLength = mb_strlen($message, 'UTF-8');

        for ($index = 0; $index < $messageLength; $index++) {
            $char = mb_substr($message, $index, 1, 'UTF-8');
            $length += str_contains(self::GSM_EXTENDED_CHARS, $char) ? 2 : 1;
        }

        return $length;
    }
}
