<?php

namespace App\Models;

use App\Domain\Orders\OrderTotals;
use App\Domain\Orders\TotalLine;
use App\Domain\Orders\TotalsCalculator;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function totals(int $discountCents = 0, ?string $country = null): OrderTotals
    {
        return app(TotalsCalculator::class)->calculate(
            $this->totalLines(),
            $discountCents,
            $country,
            $this->currency,
        );
    }
}
