<?php

use App\Livewire\Storefront\ShopBrowser;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\VariantOption;
use App\Models\VariantOptionValue;
use App\Support\Tenancy\TenantManager;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

/**
 * @param  array<string,string>  $colours  value => swatch
 */
function makeProduct(string $name, int $priceCents, array $colours = [], ?Brand $brand = null, array $categories = []): Product
{
    $product = Product::factory()->create([
        'name' => $name,
        'brand_id' => $brand?->id,
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);
    $product->categories()->sync(collect($categories)->pluck('id'));

    if ($colours === []) {
        ProductVariant::factory()->for($product)->create(['price_cents' => $priceCents, 'stock_qty' => 5]);

        return $product;
    }

    $option = VariantOption::create(['product_id' => $product->id, 'name' => 'Colour']);
    foreach ($colours as $value => $swatch) {
        $ov = VariantOptionValue::create(['variant_option_id' => $option->id, 'value' => $value, 'swatch' => $swatch]);
        $variant = ProductVariant::factory()->for($product)->create(['price_cents' => $priceCents, 'stock_qty' => 5]);
        $variant->optionValues()->attach($ov->id);
    }

    return $product;
}

it('shows active products on the shop page but hides drafts', function () {
    makeProduct('Visible Rope', 999);
    Product::factory()->draft()->create(['name' => 'Hidden Draft']);

    $this->get('/shop')
        ->assertOk()
        ->assertSee('Visible Rope')
        ->assertDontSee('Hidden Draft');
});

it('404s on a draft product detail page', function () {
    $draft = Product::factory()->draft()->create(['slug' => 'secret']);
    ProductVariant::factory()->for($draft)->create();

    $this->get('/product/secret')->assertNotFound();
});

it('renders a product detail page with its options', function () {
    $product = makeProduct('Velites Grips', 5495, ['Black' => '#000', 'Blue' => '#00f']);

    $this->get("/product/{$product->slug}")
        ->assertOk()
        ->assertSee('Velites Grips')
        ->assertSee('Colour');
});

it('filters the listing by colour', function () {
    makeProduct('Black Grips', 5000, ['Black' => '#000']);
    makeProduct('Blue Rope', 2000, ['Blue' => '#00f']);

    Livewire::test(ShopBrowser::class)
        ->assertSee('Black Grips')
        ->assertSee('Blue Rope')
        ->set('colours', ['Black'])
        ->assertSee('Black Grips')
        ->assertDontSee('Blue Rope');
});

it('filters the listing by brand and by max price', function () {
    $velites = Brand::factory()->create(['name' => 'Velites', 'slug' => 'velites']);
    $reyllen = Brand::factory()->create(['name' => 'Reyllen', 'slug' => 'reyllen']);
    makeProduct('Velites Belt', 3000, [], $velites);
    makeProduct('Reyllen Belt', 9000, [], $reyllen);

    Livewire::test(ShopBrowser::class)
        ->set('brands', ['velites'])
        ->assertSee('Velites Belt')
        ->assertDontSee('Reyllen Belt')
        ->set('brands', [])
        ->set('priceMax', 50) // €50 → 5000 cents
        ->assertSee('Velites Belt')
        ->assertDontSee('Reyllen Belt');
});

it('limits a category page to that category and its children', function () {
    $grips = Category::factory()->create(['name' => 'Grips', 'slug' => 'grips']);
    $fingerless = Category::factory()->create(['name' => 'Fingerless', 'slug' => 'fingerless', 'parent_id' => $grips->id]);
    $belts = Category::factory()->create(['name' => 'Belts', 'slug' => 'belts']);

    makeProduct('Condor Grips', 2995, [], null, [$fingerless]);
    makeProduct('Lumbar Belt', 3495, [], null, [$belts]);

    Livewire::test(ShopBrowser::class, ['categorySlug' => 'grips'])
        ->assertSee('Condor Grips')
        ->assertDontSee('Lumbar Belt');
});
