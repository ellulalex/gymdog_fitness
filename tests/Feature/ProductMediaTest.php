<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

it('stores a product image and exposes its url', function () {
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('grips.jpg', 800, 800))
        ->toMediaCollection('images');

    expect($product->hasMedia('images'))->toBeTrue()
        ->and($product->imageUrl())->not->toBeNull();
});

it('falls back to a placeholder when a product has no image', function () {
    $product = Product::factory()->create();

    expect($product->imageUrl())->toBeNull();
});

it('shows the product image on the detail page when present', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subDay()]);
    ProductVariant::factory()->for($product)->create();
    $product->addMedia(UploadedFile::fake()->image('grips.jpg', 800, 800))
        ->toMediaCollection('images');

    $this->get("/product/{$product->slug}")
        ->assertOk()
        ->assertSee('grips', false); // media filename rendered in the gallery url
});
