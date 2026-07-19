<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'code', 'type', 'value', 'min_subtotal_cents',
        'usage_limit', 'used_count', 'starts_at', 'ends_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Whether this code may be applied to the given (pre-discount) subtotal. */
    public function isValidFor(int $subtotalCents): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->min_subtotal_cents !== null && $subtotalCents < $this->min_subtotal_cents) {
            return false;
        }

        return true;
    }

    /** Discount amount in cents for the given subtotal, capped at the subtotal. */
    public function amountFor(int $subtotalCents): int
    {
        $amount = $this->type === 'fixed'
            ? $this->value
            : (int) round($subtotalCents * $this->value / 100);

        return max(0, min($amount, $subtotalCents));
    }
}
