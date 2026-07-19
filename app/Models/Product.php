<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'brand_id', 'slug', 'name', 'description', 'status',
        'tax_class', 'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class)->orderBy('position');
    }

    /** Publicly visible: active status and a past publish date. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    // --- Presentation helpers (read from variants, never hardcoded) ---

    public function lowestPriceCents(): ?int
    {
        return $this->variants->min('price_cents');
    }

    public function isOnSale(): bool
    {
        return $this->variants->contains(fn (ProductVariant $v) => $v->isOnSale());
    }

    public function inStock(): bool
    {
        return $this->variants->contains(fn (ProductVariant $v) => $v->stock_qty > 0);
    }
}
