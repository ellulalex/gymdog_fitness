<?php

use App\Domain\Payments\PaymentGateway;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\FakePaymentGateway;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
});

function paidOrder(): Order
{
    return Order::create([
        'number' => 'GD-2026-00001',
        'email' => 'buyer@example.com',
        'status' => 'completed',
        'payment_status' => 'paid',
        'currency' => 'EUR',
        'subtotal_cents' => 3000,
        'shipping_cents' => 1000,
        'tax_cents' => 610,
        'total_cents' => 4000,
        'stripe_payment_intent_id' => 'pi_123',
        'placed_at' => now(),
    ]);
}

it('renders the orders list and detail', function () {
    $order = paidOrder();

    $this->get('/admin/orders')->assertOk()->assertSee($order->number);
    $this->get("/admin/orders/{$order->getKey()}")->assertOk()->assertSee('buyer@example.com');
});

it('refunds a paid order through Stripe from the admin', function () {
    $order = paidOrder();

    Livewire::test(ViewOrder::class, ['record' => $order->getKey()])
        ->callAction('refund');

    expect($order->fresh()->payment_status)->toBe('refunded')
        ->and($this->gateway->refunds)->toHaveCount(1);
});
