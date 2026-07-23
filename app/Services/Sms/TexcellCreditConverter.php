<?php

namespace App\Services\Sms;

/**
 * Texcell USD bakiyesini panel SMS adedine çevirir.
 * Sabit birim fiyat: Bro Per SMS (varsayılan 0.0072 USD).
 */
class TexcellCreditConverter
{
    public function rate(): float
    {
        $rate = (float) config('sms.texcell.usd_per_sms', 0.0072);

        return $rate > 0 ? $rate : 0.0072;
    }

    /**
     * USD bakiyesi → satılabilir SMS adedi (aşağı yuvarlanır).
     */
    public function usdToCredits(float $usd): int
    {
        if ($usd <= 0) {
            return 0;
        }

        // float kaymasını azaltmak için küçük epsilon
        return max(0, (int) floor(($usd / $this->rate()) + 1e-9));
    }

    public function creditsToUsd(int $credits): float
    {
        return round(max(0, $credits) * $this->rate(), 6);
    }
}
