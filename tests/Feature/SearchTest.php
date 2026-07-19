<?php

use App\Livewire\Storefront\Search;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('searches products and content by term', function () {
    $product = Product::factory()->create(['name' => 'Velites Grips', 'status' => 'active', 'published_at' => now()->subDay()]);
    ProductVariant::factory()->for($product)->create(['price_cents' => 5000, 'stock_qty' => 3]);
    Post::factory()->create(['title' => 'CrossFit Nutrition Guide', 'status' => 'published', 'published_at' => now()->subDay()]);

    Livewire::test(Search::class)
        ->set('q', 'velites')
        ->assertSee('Velites Grips')
        ->assertDontSee('CrossFit Nutrition Guide');

    Livewire::test(Search::class)
        ->set('q', 'nutrition')
        ->assertSee('CrossFit Nutrition Guide');
});

it('ignores very short queries', function () {
    Product::factory()->create(['name' => 'Grips', 'status' => 'active', 'published_at' => now()->subDay()]);

    Livewire::test(Search::class)
        ->set('q', 'g')
        ->assertSee('Type at least two characters');
});
