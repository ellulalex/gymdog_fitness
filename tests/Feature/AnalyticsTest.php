<?php

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    config()->set('analytics.ga4_id', null);
    config()->set('analytics.cloudflare_token', null);
});

it('renders nothing when no analytics are configured', function () {
    $res = $this->get('/')->assertOk();

    $res->assertDontSee('googletagmanager', false)
        ->assertDontSee('cloudflareinsights', false)
        // No consent banner when there is nothing to consent to.
        ->assertDontSee('Cookie consent', false);
});

it('loads Cloudflare analytics without asking for consent', function () {
    config()->set('analytics.cloudflare_token', 'cf-token-123');

    $res = $this->get('/')->assertOk();

    $res->assertSee('cloudflareinsights.com/beacon.min.js', false)
        ->assertSee('cf-token-123', false)
        // Cookieless, so no banner is required.
        ->assertDontSee('Cookie consent', false);
});

it('loads GA4 with consent denied by default and shows the banner', function () {
    config()->set('analytics.ga4_id', 'G-TEST12345');

    $res = $this->get('/')->assertOk();

    $res->assertSee('googletagmanager.com/gtag/js?id=G-TEST12345', false)
        // Consent Mode v2: storage denied until the visitor accepts.
        ->assertSee("gtag('consent', 'default'", false)
        ->assertSee("analytics_storage: 'denied'", false)
        ->assertSee('Cookie consent', false);
});

it('emits a GA4 purchase event on the confirmation page', function () {
    config()->set('analytics.ga4_id', 'G-TEST12345');

    $order = App\Models\Order::create([
        'number' => 'GD-2026-09999',
        'email' => 'buyer@example.com',
        'status' => 'completed',
        'payment_status' => 'paid',
        'currency' => 'EUR',
        'subtotal_cents' => 3000,
        'shipping_cents' => 1000,
        'tax_cents' => 610,
        'total_cents' => 4000,
        'stripe_payment_intent_id' => 'pi_analytics',
        'placed_at' => now(),
    ]);
    $order->lines()->create([
        'name_snapshot' => 'Panda X3 Grips',
        'sku_snapshot' => 'PX3-S',
        'unit_price_cents' => 1500,
        'qty' => 2,
        'tax_rate' => 0.18,
        'tax_cents' => 610,
        'total_cents' => 3000,
    ]);

    $this->get('/checkout/confirmation?order='.$order->number)
        ->assertOk()
        ->assertSee("gtag('event', 'purchase'", false)
        ->assertSee('GD-2026-09999', false)
        ->assertSee('"item_name":"Panda X3 Grips"', false);
});
