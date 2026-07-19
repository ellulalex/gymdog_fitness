<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariantOptionValue extends Model
{
    use HasFactory;

    protected $fillable = ['variant_option_id', 'value', 'swatch', 'position'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(VariantOption::class, 'variant_option_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_value',
            'variant_option_value_id',
            'product_variant_id',
        );
    }
}
