<?php

use App\Livewire\Storefront\AddToCart;
use App\Livewire\Storefront\CartPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Cart\CartManager;
use App\Support\Tenancy\TenantManager;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function variant(int $price = 3000, int $stock = 5): ProductVariant
{
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);

    return ProductVariant::factory()->for($product)->create(['price_cents' => $price, 'stock_qty' => $stock]);
}

it('adds a variant to the cart and counts items', function () {
    $cart = app(CartManager::class)->current();
    $v = variant();

    $cart->add($v, 2);
    $cart->add($v, 1);

    expect($cart->fresh()->load('lines')->itemCount())->toBe(3)
        ->and($cart->lines()->count())->toBe(1); // same variant merges
});

it('computes cart totals through the shared calculator', function () {
    $cart = app(CartManager::class)->current();
    $cart->add(variant(3000, 5), 1); // €30 → under €50, €10 shipping
    $cart->load('lines.variant.product');

    $totals = $cart->totals();

    expect($totals->subtotalCents)->toBe(3000)
        ->and($totals->shippingCents)->toBe(1000)
        ->and($totals->totalCents)->toBe(4000);
});

it('adds to cart via the Livewire component and announces the change', function () {
    $product = variant(2000, 5)->product;
    $variantId = $product->variants->first()->id;

    Livewire::test(AddToCart::class, ['product' => $product])
        ->call('add', $variantId, 2)
        ->assertDispatched('cart-updated')
        ->assertSet('added', true);

    expect(app(CartManager::class)->current()->fresh()->load('lines')->itemCount())->toBe(2);
});

it('will not add an out-of-stock variant', function () {
    $product = variant(2000, 0)->product;
    $variantId = $product->variants->first()->id;

    Livewire::test(AddToCart::class, ['product' => $product])
        ->call('add', $variantId, 1)
        ->assertSet('added', false);

    expect(app(CartManager::class)->current()->fresh()->load('lines')->itemCount())->toBe(0);
});

it('updates and removes lines from the cart page', function () {
    $cart = app(CartManager::class)->current();
    $line = $cart->add(variant(2500, 9), 1);

    Livewire::test(CartPage::class)
        ->call('increment', $line->id)
        ->assertDispatched('cart-updated');
    expect($line->fresh()->qty)->toBe(2);

    Livewire::test(CartPage::class)->call('remove', $line->id);
    expect($cart->fresh()->load('lines')->isEmpty())->toBeTrue();
});
