<?php

namespace App\Sms\Support;

/**
 * Telefon numarası normalizasyon sınıfı.
 *
 * Türkiye numaraları panelde 5XXXXXXXXX olarak tutulur.
 * Uluslararası numaralar ülke kodu ile (ör. 4477..., 99450...) tutulur.
 */
class PhoneNormalizer
{
    /**
     * Telefon numarasını rakamlara indirger.
     * TR: 5XXXXXXXXX | Uluslararası: ülke kodu + abone (8–15 hane).
     */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Türkiye: +90 / 90 / 0XXXXXXXXXX → 5XXXXXXXXX
        if (str_starts_with($digits, '90') && strlen($digits) === 12 && $digits[2] === '5') {
            return substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '05')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Türkiye cep telefonu mu?
     */
    public function isValidTurkishMobile(string $phone): bool
    {
        $normalized = $this->normalize($phone);

        return (bool) preg_match('/^5[0-9]{9}$/', $normalized);
    }

    /**
     * Gönderilebilir alıcı numarası mı? (TR cep veya uluslararası)
     *
     * E.164 benzeri: 8–15 rakam, 0 ile başlamaz.
     */
    public function isValidRecipient(string $phone): bool
    {
        $normalized = $this->normalize($phone);

        if ($this->isValidTurkishMobile($normalized)) {
            return true;
        }

        // Uluslararası: ülke kodu dahil, 0 ile başlamayan 8–15 hane
        return (bool) preg_match('/^[1-9][0-9]{7,14}$/', $normalized);
    }
}
