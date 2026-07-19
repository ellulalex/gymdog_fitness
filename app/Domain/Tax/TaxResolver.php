<?php

namespace App\Domain\Tax;

/**
 * Resolves a VAT rate from destination country + product tax class. The rate
 * table lives in config/tax.php — never a hardcoded 18% — so crossing the EU
 * OSS threshold is a config change, not a refactor (spec §8).
 */
class TaxResolver
{
    public function rateFor(string $country, string $taxClass = 'standard'): float
    {
        $rates = config('tax.rates');
        $default = config('tax.default_country');

        return $rates[$country][$taxClass]
            ?? $rates[$default][$taxClass]
            ?? config('tax.fallback_rate');
    }

    /** Extract the tax contained in a VAT-inclusive amount, in cents. */
    public function extractFromInclusive(int $inclusiveCents, float $rate): int
    {
        if ($rate <= 0 || $inclusiveCents <= 0) {
            return 0;
        }

        return (int) round($inclusiveCents - ($inclusiveCents / (1 + $rate)));
    }
}
