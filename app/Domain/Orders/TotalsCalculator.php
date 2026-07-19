<?php

namespace App\Domain\Orders;

use App\Domain\Tax\TaxResolver;

/**
 * THE order totals calculator. Called identically from cart preview, checkout,
 * admin order creation and tests. Divergent totals logic is the most common
 * source of "the customer was charged the wrong amount" bugs, so there is
 * exactly one of these (spec §6).
 *
 * Prices are VAT-inclusive (spec §8): the total is subtotal − discount +
 * shipping, and tax is the VAT *contained within* that, extracted per line.
 */
class TotalsCalculator
{
    public function __construct(
        private readonly TaxResolver $tax,
        private readonly ShippingCalculator $shipping,
    ) {}

    /**
     * @param  TotalLine[]  $lines
     */
    public function calculate(
        array $lines,
        int $discountCents = 0,
        ?string $country = null,
        string $currency = 'EUR',
    ): OrderTotals {
        $country ??= config('tax.default_country');

        $subtotal = array_sum(array_map(fn (TotalLine $l) => $l->subtotalCents(), $lines));
        $discount = max(0, min($discountCents, $subtotal));
        $netGoods = $subtotal - $discount;

        $shippingCents = $this->shipping->cents($netGoods, $country);

        // VAT is extracted from the inclusive amounts. Any discount is spread
        // across lines in proportion to their share of the subtotal so the tax
        // reflects what the customer actually pays.
        $taxCents = 0;
        foreach ($lines as $line) {
            $lineInclusive = $line->subtotalCents();

            if ($subtotal > 0 && $discount > 0) {
                $lineInclusive -= (int) round($discount * ($line->subtotalCents() / $subtotal));
            }

            $rate = $this->tax->rateFor($country, $line->taxClass);
            $taxCents += $this->tax->extractFromInclusive($lineInclusive, $rate);
        }

        $shippingRate = $this->tax->rateFor($country, config('shipping.tax_class'));
        $taxCents += $this->tax->extractFromInclusive($shippingCents, $shippingRate);

        return new OrderTotals(
            subtotalCents: $subtotal,
            discountCents: $discount,
            shippingCents: $shippingCents,
            taxCents: $taxCents,
            totalCents: $netGoods + $shippingCents,
            currency: $currency,
        );
    }
}
