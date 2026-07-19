<?php

use App\Domain\Payments\PaymentGateway;
use App\Livewire\Storefront\Checkout;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Cart\CartManager;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Support\FakePaymentGateway;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    app()->instance(PaymentGateway::class, new FakePaymentGateway);
});

it('registers a customer and logs them in', function () {
    $this->post('/register', [
        'name' => 'Jane Buyer',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertRedirect(route('account'));

    $this->assertAuthenticated('customer');
    expect(Customer::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('logs a customer in and out', function () {
    Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret-password')]);

    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'secret-password'])
        ->assertRedirect(route('account'));
    $this->assertAuthenticated('customer');

    $this->post('/logout')->assertRedirect(route('home'));
    $this->assertGuest('customer');
});

it('rejects bad credentials', function () {
    Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret-password')]);

    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('guards the account page and shows order history', function () {
    $this->get('/account')->assertRedirect(route('login'));

    $customer = Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret-password')]);
    Order::create([
        'number' => 'GD-2026-00001', 'customer_id' => $customer->id, 'email' => 'jane@example.com',
        'status' => 'completed', 'payment_status' => 'paid', 'currency' => 'EUR',
        'total_cents' => 4000, 'placed_at' => now(),
    ]);

    $this->actingAs($customer, 'customer')->get('/account')
        ->assertOk()
        ->assertSee('Jane')
        ->assertSee('GD-2026-00001');
});

it('links a placed order to the signed-in customer', function () {
    $customer = Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret-password')]);
    $this->actingAs($customer, 'customer');

    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);
    ProductVariant::factory()->for($product)->create(['price_cents' => 3000, 'stock_qty' => 5]);
    app(CartManager::class)->current()->add($product->variants->first(), 1);

    Livewire::test(Checkout::class)
        ->assertSet('email', 'jane@example.com') // prefilled
        ->set('name', 'Jane Buyer')->set('line1', '1 St')->set('city', 'Valletta')->set('postcode', 'VLT')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::first()->customer_id)->toBe($customer->id);
});

it('shows account vs sign-in in the header depending on auth', function () {
    $this->get('/')->assertOk()->assertSee(route('login'), false);

    $customer = Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('secret-password')]);
    $this->actingAs($customer, 'customer')->get('/')->assertOk()->assertSee(route('account'), false);
});
