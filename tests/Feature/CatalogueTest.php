<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\VariantOption;
use App\Models\VariantOptionValue;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('auto-scopes products to the current tenant', function () {
    Product::factory()->create(['name' => 'Mine']);

    $other = Tenant::create(['slug' => 'other', 'name' => 'Other']);
    app(TenantManager::class)->set($other);
    Product::factory()->create(['name' => 'Theirs']);

    app(TenantManager::class)->set($this->tenant);

    expect(Product::count())->toBe(1)
        ->and(Product::first()->name)->toBe('Mine');
});

it('only lists active, published products via the active scope', function () {
    Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);
    Product::factory()->create(['status' => 'active', 'published_at' => now()->addWeek()]); // scheduled
    Product::factory()->draft()->create();

    expect(Product::active()->count())->toBe(1);
});

it('supports nested categories', function () {
    $parent = Category::factory()->create(['name' => 'Grips']);
    $child = Category::factory()->create(['name' => 'Fingerless', 'parent_id' => $parent->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->pluck('id'))->toContain($child->id)
        ->and($parent->isRoot())->toBeTrue()
        ->and($child->isRoot())->toBeFalse();
});

it('computes price, sale and stock from variants', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['price_cents' => 5495, 'stock_qty' => 0]);
    ProductVariant::factory()->for($product)->create([
        'price_cents' => 4995, 'compare_at_price_cents' => 6495, 'stock_qty' => 3,
    ]);
    $product->load('variants');

    expect($product->lowestPriceCents())->toBe(4995)
        ->and($product->isOnSale())->toBeTrue()
        ->and($product->inStock())->toBeTrue();
});

it('links variants to their option values', function () {
    $product = Product::factory()->create();
    $brand = Brand::factory()->create();
    $product->update(['brand_id' => $brand->id]);

    $option = VariantOption::create(['product_id' => $product->id, 'name' => 'Colour']);
    $black = VariantOptionValue::create(['variant_option_id' => $option->id, 'value' => 'Black', 'swatch' => '#000']);

    $variant = ProductVariant::factory()->for($product)->create();
    $variant->optionValues()->attach($black->id);

    expect($variant->optionValues->pluck('value')->all())->toBe(['Black'])
        ->and($black->fresh()->variants->pluck('id')->all())->toBe([$variant->id])
        ->and($product->brand->is($brand))->toBeTrue();
});
