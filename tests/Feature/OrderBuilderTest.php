<?php

use App\Domain\Orders\OrderBuilder;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Cart\CartManager;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function cartWith(int $price, int $qty): Cart
{
    $product = Product::factory()->create(['name' => 'Speed Rope']);
    $variant = ProductVariant::factory()->for($product)->create([
        'price_cents' => $price, 'stock_qty' => 10, 'sku' => 'ROPE-1', 'name' => 'Red',
    ]);
    $cart = app(CartManager::class)->current();
    $cart->add($variant, $qty);

    return $cart->load('lines.variant.product');
}

it('builds an order from a cart with snapshotted lines and totals', function () {
    $cart = cartWith(3000, 2); // €60 goods → free shipping

    $order = app(OrderBuilder::class)->fromCart($cart, [
        'email' => 'buyer@example.com',
        'shipping_address' => ['country' => 'MT', 'line1' => '1 Test St'],
    ]);

    expect($order->email)->toBe('buyer@example.com')
        ->and($order->status)->toBe('pending')
        ->and($order->payment_status)->toBe('unpaid')
        ->and($order->subtotal_cents)->toBe(6000)
        ->and($order->shipping_cents)->toBe(0)
        ->and($order->total_cents)->toBe(6000)
        ->and($order->number)->toStartWith('GD-');

    $line = $order->lines->first();
    expect($line->name_snapshot)->toBe('Speed Rope (Red)')
        ->and($line->sku_snapshot)->toBe('ROPE-1')
        ->and($line->unit_price_cents)->toBe(3000)
        ->and($line->qty)->toBe(2)
        ->and($line->total_cents)->toBe(6000);
});

it('carries the €10 shipping rule onto the order', function () {
    $cart = cartWith(3000, 1); // €30 → €10 shipping

    $order = app(OrderBuilder::class)->fromCart($cart, [
        'email' => 'buyer@example.com',
        'shipping_address' => ['country' => 'MT'],
    ]);

    expect($order->shipping_cents)->toBe(1000)
        ->and($order->total_cents)->toBe(4000);
});

it('keeps the line snapshot after the variant is deleted', function () {
    $cart = cartWith(2500, 1);
    $order = app(OrderBuilder::class)->fromCart($cart, [
        'email' => 'buyer@example.com',
        'shipping_address' => ['country' => 'MT'],
    ]);

    $cart->lines->first()->variant->delete();

    $line = $order->fresh()->lines->first();
    expect($line->product_variant_id)->toBeNull()
        ->and($line->name_snapshot)->toBe('Speed Rope (Red)')
        ->and($line->total_cents)->toBe(2500);
});

it('generates unique sequential order numbers', function () {
    $order1 = app(OrderBuilder::class)->fromCart(cartWith(2000, 1), ['email' => 'a@example.com', 'shipping_address' => ['country' => 'MT']]);
    $order2 = app(OrderBuilder::class)->fromCart(cartWith(2000, 1), ['email' => 'b@example.com', 'shipping_address' => ['country' => 'MT']]);

    expect($order1->number)->not->toBe($order2->number);
});
