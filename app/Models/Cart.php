<?php

namespace App\Models;

use App\Domain\Orders\OrderTotals;
use App\Domain\Orders\TotalLine;
use App\Domain\Orders\TotalsCalculator;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;

class Cart extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'session_token', 'customer_id', 'currency'];

    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class);
    }

    public function add(ProductVariant $variant, int $qty = 1): CartLine
    {
        $line = $this->lines()->firstOrNew(['product_variant_id' => $variant->id]);
        $line->qty = ($line->qty ?? 0) + max(1, $qty);
        $line->save();

        return $line;
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum('qty');
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /** @return TotalLine[] */
    public function totalLines(): array
    {
        return $this->lines
            ->filter(fn (CartLine $line) => $line->variant !== null)
            ->map(fn (CartLine $line) => new TotalLine(
                unitPriceCents: $line->variant->price_cents,
                qty: $line->qty,
                taxClass: $line->variant->product->tax_class ?? 'standard',
            ))
            ->all();
    }

    public function subtotalCents(): int
    {
        return array_sum(array_map(fn (TotalLine $l) => $l->subtotalCents(), $this->totalLines()));
    }

    // --- Discounts (applied code kept in the session, 1:1 with the cart) ---

    public function appliedCode(): ?string
    {
        return Session::get('cart_discount');
    }

    /** The applied discount if it still validates against the current subtotal. */
    public function appliedDiscount(): ?Discount
    {
        $code = $this->appliedCode();

        if (! $code) {
            return null;
        }

        $discount = Discount::where('code', $code)->first();

        return $discount && $discount->isValidFor($this->subtotalCents()) ? $discount : null;
    }

    public function discountCents(): int
    {
        $discount = $this->appliedDiscount();

        return $discount ? $discount->amountFor($this->subtotalCents()) : 0;
    }

    /** Try to apply a code; returns false (and applies nothing) if invalid. */
    public function applyCode(string $code): bool
    {
        $discount = Discount::where('code', trim($code))->first();

        if (! $discount || ! $discount->isValidFor($this->subtotalCents())) {
            return false;
        }

        Session::put('cart_discount', $discount->code);

        return true;
    }

    public function removeDiscount(): void
    {
        Session::forget('cart_discount');
    }

    public function totals(?string $country = null): OrderTotals
    {
        return app(TotalsCalculator::class)->calculate(
            $this->totalLines(),
            $this->discountCents(),
            $country,
            $this->currency,
        );
    }
}
