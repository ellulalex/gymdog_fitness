<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'product_variant_id', 'reference', 'supplier',
        'unit_cost_cents', 'quantity', 'quantity_remaining', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'date'];
    }

    protected static function booted(): void
    {
        // Receiving a consignment fills its remaining quantity and adds it to
        // the variant's stock — for both the admin and the receive() helper.
        static::creating(function (Consignment $consignment) {
            if ($consignment->quantity_remaining === null) {
                $consignment->quantity_remaining = $consignment->quantity;
            }
        });

        static::created(function (Consignment $consignment) {
            $consignment->variant?->increment('stock_qty', $consignment->quantity);
        });
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Oldest first — the FIFO draw-down order. */
    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderBy('received_at')->orderBy('id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('quantity_remaining', '>', 0);
    }
}
