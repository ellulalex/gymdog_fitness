<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $fillable = [
        'order_id', 'product_variant_id', 'name_snapshot', 'sku_snapshot',
        'unit_price_cents', 'qty', 'tax_rate', 'tax_cents', 'total_cents', 'cost_cents',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** May be null if the variant was later deleted — the snapshot still holds. */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
