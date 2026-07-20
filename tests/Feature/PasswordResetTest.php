<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('renders the forgot and reset pages', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?');
    $this->get('/reset-password/some-token')->assertOk()->assertSee('Choose a new password');
});

it('emails a reset link and resets the password end-to-end', function () {
    Notification::fake();
    $customer = Customer::create([
        'name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('old-password'),
    ]);

    $this->post('/forgot-password', ['email' => 'jane@example.com'])->assertSessionHas('status');

    $token = null;
    Notification::assertSentTo($customer, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'jane@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password-123', $customer->fresh()->password))->toBeTrue();

    // The new password works.
    $this->post('/login', ['email' => 'jane@example.com', 'password' => 'new-password-123'])
        ->assertRedirect(route('account'));
});
