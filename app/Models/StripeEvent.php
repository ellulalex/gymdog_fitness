<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records every processed Stripe webhook event so at-least-once delivery can be
 * de-duplicated (spec §7).
 */
class StripeEvent extends Model
{
    protected $fillable = ['event_id', 'type', 'processed_at'];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
