<?php

use App\Models\Discount;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('shows featured products, latest articles, the promo code and trust badges', function () {
    $product = Product::factory()->create(['name' => 'Velites Grips', 'status' => 'active', 'published_at' => now()->subDay()]);
    ProductVariant::factory()->for($product)->create(['price_cents' => 5000, 'stock_qty' => 3]);
    Post::factory()->create(['title' => 'CrossFit Nutrition', 'status' => 'published', 'published_at' => now()->subDay()]);
    Discount::create(['code' => 'GYMDOG5', 'type' => 'percentage', 'value' => 5, 'status' => 'active']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Velites Grips')           // featured product
        ->assertSee('CrossFit Nutrition')      // latest article
        ->assertSee('GYMDOG5')                 // promo code
        ->assertSee('5% off')                  // promo label
        ->assertSee('Free delivery over', false); // trust badge
});

it('hides empty homepage sections gracefully', function () {
    // No products/articles/promo — the page still renders (just the hero + badges).
    $this->get('/')->assertOk()->assertSee("Malta's CrossFit Portal", false);
});
