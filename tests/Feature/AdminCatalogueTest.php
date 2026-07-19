<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the catalogue admin pages', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    '/admin/products',
    '/admin/products/create',
    '/admin/brands',
    '/admin/brands/create',
    '/admin/categories',
    '/admin/categories/create',
    '/admin/consignments',
    '/admin/consignments/create',
]);

it('opens the product edit page with its relation managers', function () {
    $product = Product::factory()->create();

    $this->get("/admin/products/{$product->getRouteKey()}/edit")->assertOk();
});

it('creates a product through Filament with tenant auto-fill', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Velites Hand Grips',
            'slug' => 'velites-hand-grips',
            'status' => 'active',
            'tax_class' => 'standard',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('slug', 'velites-hand-grips');

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Velites Hand Grips')
        ->and($product->tenant_id)->toBe($this->tenant->id);
});
