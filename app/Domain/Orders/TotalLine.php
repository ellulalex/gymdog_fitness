<?php

namespace App\Domain\Orders;

/**
 * A single line fed to the TotalsCalculator. Cart lines and order lines both
 * map to this, so the calculator has one input shape regardless of caller.
 */
final readonly class TotalLine
{
    public function __construct(
        public int $unitPriceCents,
        public int $qty,
        public string $taxClass = 'standard',
    ) {}

    public function subtotalCents(): int
    {
        return $this->unitPriceCents * $this->qty;
    }
}
