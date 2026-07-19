<?php

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use App\Support\Theme\ThemeManager;

it('uses the tenant theme when one is set', function () {
    $tenant = Tenant::create([
        'slug' => 'acme',
        'name' => 'Acme',
        'settings' => ['theme' => 'default'],
    ]);
    app(TenantManager::class)->set($tenant);

    expect(app(ThemeManager::class)->active())->toBe('default');
});

it('falls back to the configured default theme when the tenant sets none', function () {
    config()->set('themes.default', 'gymdog');
    app(TenantManager::class)->set(Tenant::create(['slug' => 'x', 'name' => 'X']));

    expect(app(ThemeManager::class)->active())->toBe('gymdog');
});

it('renders the gymdog theme home over the default theme', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee("Malta's CrossFit Portal", false);
});

it('serves the shop and the blog without 404', function () {
    $this->get('/shop')->assertOk();
    $this->get('/blog')->assertOk();
});
