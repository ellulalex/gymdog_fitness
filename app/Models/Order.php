<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'number', 'customer_id', 'email',
        'status', 'payment_status', 'fulfilment_status',
        'currency', 'subtotal_cents', 'discount_cents', 'shipping_cents',
        'tax_cents', 'total_cents', 'billing_address', 'shipping_address',
        'stripe_payment_intent_id', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function markPaid(): void
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'completed',
            'placed_at' => $this->placed_at ?? now(),
        ]);
    }
}
