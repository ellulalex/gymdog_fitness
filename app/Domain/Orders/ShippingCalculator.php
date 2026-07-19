<?php

namespace App\Domain\Orders;

/**
 * Malta shipping: flat €10 under €50, free at/above €50 (config/shipping.php).
 * The threshold is checked against the post-discount goods subtotal.
 */
class ShippingCalculator
{
    public function shipsTo(string $country): bool
    {
        return in_array($country, config('shipping.countries'), true);
    }

    public function cents(int $goodsSubtotalCents, string $country): int
    {
        if ($goodsSubtotalCents <= 0) {
            return 0;
        }

        if ($goodsSubtotalCents >= config('shipping.free_threshold_cents')) {
            return 0;
        }

        return config('shipping.flat_cents');
    }
}
