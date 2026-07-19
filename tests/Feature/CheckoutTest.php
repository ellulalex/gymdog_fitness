<?php

use App\Domain\Orders\OrderBuilder;
use App\Domain\Payments\PaymentGateway;
use App\Livewire\Storefront\Checkout;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StripeEvent;
use App\Models\Tenant;
use App\Support\Cart\CartManager;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\FakePaymentGateway;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
});

function seedVariant(int $price = 3000, int $stock = 5): ProductVariant
{
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);

    return ProductVariant::factory()->for($product)->create(['price_cents' => $price, 'stock_qty' => $stock]);
}

function fakeEvent(string $type, string $intentId, string $id = 'evt_1'): array
{
    return ['id' => $id, 'type' => $type, 'data' => ['object' => ['id' => $intentId, 'payment_intent' => $intentId]]];
}

it('redirects to the shop when checkout starts with an empty cart', function () {
    Livewire::test(Checkout::class)->assertRedirect(route('shop'));
});

it('places an order and creates a payment intent', function () {
    app(CartManager::class)->current()->add(seedVariant(3000, 5), 1);

    Livewire::test(Checkout::class)
        ->set('email', 'buyer@example.com')
        ->set('name', 'Test Buyer')
        ->set('line1', '1 Test St')
        ->set('city', 'Valletta')
        ->set('postcode', 'VLT1000')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertSet('step', 'pay')
        ->assertSet('clientSecret', fn ($v) => filled($v));

    $order = Order::first();
    expect($order->email)->toBe('buyer@example.com')
        ->and($order->payment_status)->toBe('unpaid')
        ->and($order->total_cents)->toBe(4000) // €30 + €10 shipping
        ->and($order->stripe_payment_intent_id)->toBe('pi_fake_'.$order->id);
});

it('validates checkout fields', function () {
    app(CartManager::class)->current()->add(seedVariant(), 1);

    Livewire::test(Checkout::class)
        ->set('email', 'not-an-email')
        ->call('placeOrder')
        ->assertHasErrors(['email', 'name', 'line1', 'city', 'postcode']);
});

it('marks the order paid, draws down stock and emails on payment_intent.succeeded', function () {
    Mail::fake();
    $variant = seedVariant(3000, 5);
    app(CartManager::class)->current()->add($variant, 2);
    $order = app(OrderBuilder::class)->fromCart(
        app(CartManager::class)->current()->load('lines.variant.product'),
        ['email' => 'buyer@example.com', 'shipping_address' => ['country' => 'MT']],
    );
    app(PaymentGateway::class)->createIntent($order);

    $this->postJson('/stripe/webhook', fakeEvent('payment_intent.succeeded', $order->stripe_payment_intent_id))
        ->assertOk();

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and($variant->fresh()->stock_qty)->toBe(3); // 5 − 2
    Mail::assertSent(OrderConfirmation::class);
});

it('ignores a duplicate webhook delivery', function () {
    Mail::fake();
    $variant = seedVariant(3000, 5);
    app(CartManager::class)->current()->add($variant, 2);
    $order = app(OrderBuilder::class)->fromCart(
        app(CartManager::class)->current()->load('lines.variant.product'),
        ['email' => 'b@example.com', 'shipping_address' => ['country' => 'MT']],
    );
    app(PaymentGateway::class)->createIntent($order);
    $event = fakeEvent('payment_intent.succeeded', $order->stripe_payment_intent_id);

    $this->postJson('/stripe/webhook', $event)->assertOk();
    $this->postJson('/stripe/webhook', $event)->assertOk();

    expect($variant->fresh()->stock_qty)->toBe(3) // decremented once, not twice
        ->and(StripeEvent::count())->toBe(1);
    Mail::assertSentCount(1);
});

it('rejects a webhook with an invalid payload', function () {
    // Fake gateway throws on non-JSON — controller returns 400.
    $this->call('POST', '/stripe/webhook', [], [], [], [], 'not json')
        ->assertStatus(400);
});
