<?php

namespace Tests\Unit\Sms;

use App\Services\Sms\TexcellCreditConverter;
use Tests\TestCase;

class TexcellCreditConverterTest extends TestCase
{
    public function test_converts_usd_to_sms_credits_at_fixed_rate(): void
    {
        config(['sms.texcell.usd_per_sms' => 0.0072]);

        $converter = new TexcellCreditConverter;

        $this->assertSame(0.0072, $converter->rate());
        $this->assertSame(0, $converter->usdToCredits(0));
        $this->assertSame(0, $converter->usdToCredits(0.0071));
        $this->assertSame(1, $converter->usdToCredits(0.0072));
        $this->assertSame(1000, $converter->usdToCredits(7.2));
        $this->assertSame(1388, $converter->usdToCredits(10)); // floor(10/0.0072)=1388
    }
}
