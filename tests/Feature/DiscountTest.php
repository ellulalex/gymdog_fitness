<?php

use App\Domain\Orders\OrderBuilder;
use App\Livewire\Storefront\CartPage;
use App\Models\Cart;
use App\Models\Discount;
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

function code(array $attrs = []): Discount
{
    return Discount::create(array_merge([
        'code' => 'GYMDOG5', 'type' => 'percentage', 'value' => 5, 'status' => 'active',
    ], $attrs));
}

function cartOf(int $price, int $qty = 1): Cart
{
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_cents' => $price, 'stock_qty' => 9]);
    $cart = app(CartManager::class)->current();
    $cart->add($variant, $qty);

    return $cart->load('lines.variant.product');
}

it('computes percentage and fixed amounts, capped at subtotal', function () {
    expect(code(['type' => 'percentage', 'value' => 5])->amountFor(3000))->toBe(150)
        ->and(code(['code' => 'TEN', 'type' => 'fixed', 'value' => 1000])->amountFor(3000))->toBe(1000)
        ->and(code(['code' => 'BIG', 'type' => 'fixed', 'value' => 9999])->amountFor(3000))->toBe(3000);
});

it('validates status, window, usage limit and minimum subtotal', function () {
    expect(code(['status' => 'disabled'])->isValidFor(3000))->toBeFalse()
        ->and(code(['code' => 'PAST', 'ends_at' => now()->subDay()])->isValidFor(3000))->toBeFalse()
        ->and(code(['code' => 'USED', 'usage_limit' => 1, 'used_count' => 1])->isValidFor(3000))->toBeFalse()
        ->and(code(['code' => 'MIN', 'min_subtotal_cents' => 5000])->isValidFor(3000))->toBeFalse()
        ->and(code(['code' => 'OK'])->isValidFor(3000))->toBeTrue();
});

it('applies a code to the cart and reflects it in totals', function () {
    code();
    $cart = cartOf(3000);

    expect($cart->applyCode('GYMDOG5'))->toBeTrue();

    $totals = $cart->totals();
    expect($totals->discountCents)->toBe(150)   // 5% of €30
        ->and($totals->shippingCents)->toBe(1000)
        ->and($totals->totalCents)->toBe(3850);  // 3000 − 150 + 1000
});

it('rejects an invalid code', function () {
    $cart = cartOf(3000);

    expect($cart->applyCode('NOPE'))->toBeFalse()
        ->and($cart->discountCents())->toBe(0);
});

it('records the code on the order and increments usage', function () {
    code();
    $cart = cartOf(3000);
    $cart->applyCode('GYMDOG5');

    $order = app(OrderBuilder::class)->fromCart($cart, [
        'email' => 'b@example.com', 'shipping_address' => ['country' => 'MT'],
    ]);

    expect($order->discount_cents)->toBe(150)
        ->and($order->discount_code)->toBe('GYMDOG5')
        ->and(Discount::where('code', 'GYMDOG5')->first()->used_count)->toBe(1);
});

it('applies and rejects codes from the cart page', function () {
    code();
    cartOf(3000);

    Livewire::test(CartPage::class)
        ->set('code', 'GYMDOG5')
        ->call('applyCode')
        ->assertDispatched('cart-updated')
        ->assertSet('discountError', null);

    Livewire::test(CartPage::class)
        ->set('code', 'WRONG')
        ->call('applyCode')
        ->assertSet('discountError', fn ($v) => filled($v));
});
