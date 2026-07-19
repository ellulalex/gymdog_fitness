<?php

namespace App\Domain\Orders;

use App\Domain\Tax\TaxResolver;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Turns a cart into a persisted, snapshotted order. Everything the order needs
 * to render forever is copied onto it, so later edits/deletes of products can't
 * change historical orders (spec §5). Totals come from the shared
 * TotalsCalculator — the same money the cart quoted.
 */
class OrderBuilder
{
    public function __construct(
        private readonly TotalsCalculator $totals,
        private readonly TaxResolver $tax,
    ) {}

    /**
     * @param  array{email:string, billing_address?:array, shipping_address?:array, discount_cents?:int}  $data
     */
    public function fromCart(Cart $cart, array $data): Order
    {
        $cart->loadMissing('lines.variant.product');

        $shipping = $data['shipping_address'] ?? [];
        $country = $shipping['country'] ?? config('tax.default_country');

        $discountModel = $cart->appliedDiscount();
        $discount = $discountModel
            ? $discountModel->amountFor($cart->subtotalCents())
            : ($data['discount_cents'] ?? 0);

        $totals = $this->totals->calculate($cart->totalLines(), $discount, $country, $cart->currency);

        return DB::transaction(function () use ($cart, $data, $shipping, $country, $totals, $discountModel) {
            $order = Order::create([
                'number' => $this->nextNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'email' => $data['email'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'currency' => $totals->currency,
                'subtotal_cents' => $totals->subtotalCents,
                'discount_cents' => $totals->discountCents,
                'discount_code' => $discountModel?->code,
                'shipping_cents' => $totals->shippingCents,
                'tax_cents' => $totals->taxCents,
                'total_cents' => $totals->totalCents,
                'billing_address' => $data['billing_address'] ?? $shipping,
                'shipping_address' => $shipping,
            ]);

            $discountModel?->increment('used_count');

            foreach ($cart->lines as $line) {
                $variant = $line->variant;

                if (! $variant) {
                    continue;
                }

                $taxClass = $variant->product->tax_class ?? 'standard';
                $rate = $this->tax->rateFor($country, $taxClass);
                $lineTotal = $variant->price_cents * $line->qty;

                $order->lines()->create([
                    'product_variant_id' => $variant->id,
                    'name_snapshot' => trim($variant->product->name.' '.($variant->name ? "({$variant->name})" : '')),
                    'sku_snapshot' => $variant->sku,
                    'unit_price_cents' => $variant->price_cents,
                    'qty' => $line->qty,
                    'tax_rate' => $rate,
                    'tax_cents' => $this->tax->extractFromInclusive($lineTotal, $rate),
                    'total_cents' => $lineTotal,
                ]);
            }

            return $order;
        });
    }

    /** Human-friendly, per-tenant sequential order number. */
    protected function nextNumber(): string
    {
        $sequence = Order::query()->count() + 1;

        do {
            $number = 'GD-'.now()->format('Y').'-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Order::where('number', $number)->exists());

        return $number;
    }
}
