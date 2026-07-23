<?php

namespace Tests\Unit\Sms;

use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use Tests\TestCase;

/**
 * SMS yardımcı sınıf unit testleri.
 */
class SmsSupportTest extends TestCase
{
    /**
     * Türkçe karakterli mesajın Unicode segment hesaplandığını doğrular.
     */
    public function test_segment_calculator_handles_turkish_characters(): void
    {
        $calculator = new SmsSegmentCalculator;

        $this->assertTrue($calculator->requiresUnicodeEncoding('Merhaba şğüöç'));
        $this->assertSame(1, $calculator->calculateSegments('Merhaba'));
        $this->assertSame(1, $calculator->calculateCreditsUsed('Test'));
    }

    /**
     * Telefon normalizasyonunu doğrular.
     */
    public function test_phone_normalizer_validates_turkish_mobile(): void
    {
        $normalizer = new PhoneNormalizer;

        $this->assertSame('5551234567', $normalizer->normalize('+90 555 123 45 67'));
        $this->assertSame('5551234567', $normalizer->normalize('05551234567'));
        $this->assertTrue($normalizer->isValidTurkishMobile('5551234567'));
        $this->assertFalse($normalizer->isValidTurkishMobile('12345'));
    }

    public function test_phone_normalizer_accepts_international_numbers(): void
    {
        $normalizer = new PhoneNormalizer;

        $this->assertSame('447700900123', $normalizer->normalize('+44 7700 900123'));
        $this->assertSame('994501234567', $normalizer->normalize('00994501234567'));
        $this->assertTrue($normalizer->isValidRecipient('+447700900123'));
        $this->assertTrue($normalizer->isValidRecipient('905551234567'));
        $this->assertTrue($normalizer->isValidRecipient('5551234567'));
        $this->assertFalse($normalizer->isValidRecipient('12345'));
        $this->assertFalse($normalizer->isValidRecipient('0123456789'));
    }
}
