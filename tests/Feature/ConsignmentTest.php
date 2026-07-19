<?php

use App\Domain\Payments\PaymentGateway;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Mail;
use Tests\Support\FakePaymentGateway;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function newVariant(int $stock = 0): ProductVariant
{
    $product = Product::factory()->create();

    return ProductVariant::factory()->for($product)->create(['price_cents' => 5000, 'stock_qty' => $stock]);
}

it('receiving a consignment adds to stock and records remaining', function () {
    $variant = newVariant(0);

    $c = $variant->receive(10, 3000, ['reference' => 'INV-1']);

    expect($variant->fresh()->stock_qty)->toBe(10)
        ->and($c->quantity_remaining)->toBe(10)
        ->and($c->unit_cost_cents)->toBe(3000);
});

it('reports latest and weighted-average cost across consignments', function () {
    $variant = newVariant(0);
    $variant->receive(10, 3000);          // 10 @ €30
    $variant->receive(10, 3600);          // 10 @ €36 (price went up)

    expect($variant->latestCostCents())->toBe(3600)
        ->and($variant->averageCostCents())->toBe(3300); // (10*3000 + 10*3600)/20
});

it('draws down cost FIFO — oldest consignment first', function () {
    $variant = newVariant(0);
    $variant->receive(10, 3000);          // batch 1: 10 @ €30
    $variant->receive(10, 3600);          // batch 2: 10 @ €36

    // Sell 12 → 10 from batch 1 (€300) + 2 from batch 2 (€72) = €372.
    $cogs = $variant->drawDownFifo(12);

    expect($cogs)->toBe(37200);
    $batches = $variant->consignments()->fifo()->get();
    expect($batches[0]->fresh()->quantity_remaining)->toBe(0)
        ->and($batches[1]->fresh()->quantity_remaining)->toBe(8);
});

it('falls back to the latest cost when consignments do not cover the quantity', function () {
    $variant = newVariant(0);
    $variant->receive(5, 3000);           // only 5 in stock at cost

    $cogs = $variant->drawDownFifo(8);    // 5 @ €30 + 3 @ latest (€30) = €240

    expect($cogs)->toBe(24000);
});

it('records cost of goods on the order line when payment succeeds', function () {
    Mail::fake();
    $gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $gateway);

    $variant = newVariant(0);
    $variant->receive(10, 3000);          // cost €30/unit

    $order = Order::create([
        'number' => 'GD-2026-00001', 'email' => 'b@example.com',
        'status' => 'pending', 'payment_status' => 'unpaid', 'currency' => 'EUR',
        'total_cents' => 10000, 'stripe_payment_intent_id' => 'pi_x',
    ]);
    $order->lines()->create([
        'product_variant_id' => $variant->id, 'name_snapshot' => 'X',
        'unit_price_cents' => 5000, 'qty' => 2, 'total_cents' => 10000,
    ]);

    $this->postJson('/stripe/webhook', [
        'id' => 'evt_1', 'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_x']],
    ])->assertOk();

    $line = $order->lines->first()->fresh();
    expect($line->cost_cents)->toBe(6000)              // 2 units @ €30
        ->and($variant->fresh()->stock_qty)->toBe(8)   // 10 − 2
        ->and($variant->consignments()->first()->fresh()->quantity_remaining)->toBe(8);
});
