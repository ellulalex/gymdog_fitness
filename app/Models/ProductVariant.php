<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
