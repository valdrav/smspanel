<?php

namespace App\Sms\Support;

/**
 * Telefon numarası normalizasyon sınıfı.
 */
class PhoneNormalizer
{
    /**
     * Telefon numarasını standart formata dönüştürür (5XXXXXXXXX).
     */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Türkiye cep telefonu numarasının geçerli olup olmadığını kontrol eder.
     */
    public function isValidTurkishMobile(string $phone): bool
    {
        $normalized = $this->normalize($phone);

        return (bool) preg_match('/^5[0-9]{9}$/', $normalized);
    }
}
