<?php

use App\Livewire\Storefront\AddToCart;
use App\Livewire\Storefront\WishlistPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use App\Support\Wishlist\WishlistManager;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function wishlistProduct(): Product
{
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);
    ProductVariant::factory()->for($product)->create(['price_cents' => 2000, 'stock_qty' => 5]);

    return $product;
}

it('toggles a product on and off the wishlist', function () {
    $manager = app(WishlistManager::class);

    $manager->toggle(7);
    expect($manager->has(7))->toBeTrue()->and($manager->count())->toBe(1);

    $manager->toggle(7);
    expect($manager->has(7))->toBeFalse()->and($manager->count())->toBe(0);
});

it('adds to the wishlist from the product page', function () {
    $product = wishlistProduct();

    Livewire::test(AddToCart::class, ['product' => $product])
        ->call('toggleWishlist')
        ->assertDispatched('wishlist-updated')
        ->assertSet('inWishlist', fn () => true); // re-render reads manager

    expect(app(WishlistManager::class)->has($product->id))->toBeTrue();
});

it('lists and removes wishlist items', function () {
    $product = wishlistProduct();
    app(WishlistManager::class)->toggle($product->id);

    Livewire::test(WishlistPage::class)
        ->assertSee($product->name)
        ->call('remove', $product->id)
        ->assertDispatched('wishlist-updated');

    expect(app(WishlistManager::class)->has($product->id))->toBeFalse();
});
