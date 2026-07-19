<?php

use App\Domain\Orders\ShippingCalculator;
use App\Domain\Orders\TotalLine;
use App\Domain\Orders\TotalsCalculator;

function calc(): TotalsCalculator
{
    return app(TotalsCalculator::class);
}

it('returns zeros for an empty cart', function () {
    $totals = calc()->calculate([]);

    expect($totals->subtotalCents)->toBe(0)
        ->and($totals->shippingCents)->toBe(0)
        ->and($totals->taxCents)->toBe(0)
        ->and($totals->totalCents)->toBe(0);
});

it('extracts 18% VAT from a VAT-inclusive line', function () {
    // €54.95 inclusive → net €46.57, VAT €8.38.
    $totals = calc()->calculate([new TotalLine(5495, 1)]);

    expect($totals->subtotalCents)->toBe(5495)
        ->and($totals->taxCents)->toBe(838)
        ->and($totals->totalCents)->toBe(5495)
        ->and($totals->shippingCents)->toBe(0); // ≥ €50 ships free
});

it('charges €10 shipping under €50 and adds it to the total', function () {
    $totals = calc()->calculate([new TotalLine(3000, 1)]);

    expect($totals->subtotalCents)->toBe(3000)
        ->and($totals->shippingCents)->toBe(1000)
        ->and($totals->totalCents)->toBe(4000)
        // VAT on goods (458) + VAT on shipping (153).
        ->and($totals->taxCents)->toBe(611);
});

it('ships free at exactly the €50 threshold', function () {
    $totals = calc()->calculate([new TotalLine(5000, 1)]);

    expect($totals->shippingCents)->toBe(0)
        ->and($totals->totalCents)->toBe(5000);
});

it('multiplies unit price by quantity', function () {
    $totals = calc()->calculate([new TotalLine(1000, 3)]);

    expect($totals->subtotalCents)->toBe(3000);
});

it('applies a discount and re-evaluates the free-shipping threshold on the net', function () {
    // Two €30 lines = €60 (would ship free), less €15 discount = €45 net → €10 shipping.
    $totals = calc()->calculate(
        [new TotalLine(3000, 1), new TotalLine(3000, 1)],
        discountCents: 1500,
    );

    expect($totals->subtotalCents)->toBe(6000)
        ->and($totals->discountCents)->toBe(1500)
        ->and($totals->shippingCents)->toBe(1000)
        ->and($totals->totalCents)->toBe(5500);
});

it('caps the discount at the subtotal', function () {
    $totals = calc()->calculate([new TotalLine(3000, 1)], discountCents: 99999);

    expect($totals->discountCents)->toBe(3000)
        ->and($totals->totalCents)->toBe(0)
        ->and($totals->taxCents)->toBe(0);
});

it('charges no VAT on a zero-rated line', function () {
    $totals = calc()->calculate([new TotalLine(5000, 1, 'zero')]);

    expect($totals->taxCents)->toBe(0);
});

it('knows which countries it ships to', function () {
    $shipping = app(ShippingCalculator::class);

    expect($shipping->shipsTo('MT'))->toBeTrue()
        ->and($shipping->shipsTo('DE'))->toBeFalse();
});
