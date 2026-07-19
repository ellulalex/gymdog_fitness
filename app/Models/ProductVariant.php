<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single sellable SKU. Tenancy is inherited through the parent product, so
 * this model deliberately does not carry its own tenant_id (spec §5).
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'name', 'price_cents', 'compare_at_price_cents',
        'stock_qty', 'weight_grams', 'barcode', 'position',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            VariantOptionValue::class,
            'product_variant_option_value',
            'product_variant_id',
            'variant_option_value_id',
        );
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price_cents !== null
            && $this->compare_at_price_cents > $this->price_cents;
    }

    public function discountPercent(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return (int) round(
            (($this->compare_at_price_cents - $this->price_cents) / $this->compare_at_price_cents) * 100
        );
    }

    // --- Consignments / cost of goods ---

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    /**
     * Log a new delivery at a given unit cost. The Consignment model fills the
     * remaining quantity and adds it to this variant's stock.
     */
    public function receive(int $quantity, int $unitCostCents, array $attributes = []): Consignment
    {
        return $this->consignments()->create(array_merge([
            'unit_cost_cents' => $unitCostCents,
            'quantity' => $quantity,
            'received_at' => now()->toDateString(),
        ], $attributes));
    }

    /**
     * Draw `qty` units from the oldest consignments first and return the cost
     * of those goods (cents). Units beyond what consignments cover fall back to
     * the latest known unit cost (0 if none logged yet).
     */
    public function drawDownFifo(int $qty): int
    {
        $remaining = $qty;
        $cost = 0;

        foreach ($this->consignments()->available()->fifo()->get() as $consignment) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $consignment->quantity_remaining);
            $consignment->decrement('quantity_remaining', $take);
            $cost += $take * $consignment->unit_cost_cents;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            $cost += $remaining * ($this->latestCostCents() ?? 0);
        }

        return $cost;
    }

    /** Weighted-average unit cost of the stock currently on hand, or null. */
    public function averageCostCents(): ?int
    {
        $rows = $this->consignments()->available()->get();
        $units = (int) $rows->sum('quantity_remaining');

        if ($units === 0) {
            return null;
        }

        $total = $rows->sum(fn (Consignment $c) => $c->quantity_remaining * $c->unit_cost_cents);

        return (int) round($total / $units);
    }

    public function latestCostCents(): ?int
    {
        return $this->consignments()->orderByDesc('received_at')->orderByDesc('id')->value('unit_cost_cents');
    }
}
