<?php

namespace App\Domain\Orders;

/**
 * Immutable result of the TotalsCalculator. All values integer cents. `taxCents`
 * is the VAT contained within the (inclusive) total, shown for the receipt —
 * it is not added on top.
 */
final readonly class OrderTotals
{
    public function __construct(
        public int $subtotalCents,
        public int $discountCents,
        public int $shippingCents,
        public int $taxCents,
        public int $totalCents,
        public string $currency = 'EUR',
    ) {}

    /** @return array<string,int|string> */
    public function toArray(): array
    {
        return [
            'subtotal_cents' => $this->subtotalCents,
            'discount_cents' => $this->discountCents,
            'shipping_cents' => $this->shippingCents,
            'tax_cents' => $this->taxCents,
            'total_cents' => $this->totalCents,
            'currency' => $this->currency,
        ];
    }
}
