<?php

use App\Domain\Catalogue\WooCommerceImporter;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = Tenant::create(['slug' => 'gymdog', 'name' => 'GymDog']);
    app(TenantManager::class)->set($this->tenant);
});

function fakeStore(): void
{
    Http::fake([
        '*/wc/store/v1/products*' => Http::response([
            [
                'name' => 'Velites Hand Grips All Terrain', 'slug' => 'velites-hand-grips-all-terrain',
                'type' => 'variable', 'on_sale' => false, 'is_in_stock' => true,
                'description' => '<p>Grips</p>', 'short_description' => 'Short',
                'prices' => ['price' => '5495', 'regular_price' => '5495', 'sale_price' => '5495'],
                'categories' => [['name' => 'Grips', 'slug' => 'grips'], ['name' => 'Fingerless', 'slug' => 'fingerless']],
                'attributes' => [
                    ['name' => 'color', 'terms' => [['name' => 'Black', 'slug' => 'black'], ['name' => 'Green', 'slug' => 'green']]],
                    ['name' => 'sizes', 'terms' => [['name' => 'M', 'slug' => 'size-medium'], ['name' => 'L', 'slug' => 'size-large']]],
                ],
                'variations' => [
                    ['attributes' => [['name' => 'color', 'value' => 'black'], ['name' => 'sizes', 'value' => 'size-medium']]],
                    ['attributes' => [['name' => 'color', 'value' => 'black'], ['name' => 'sizes', 'value' => 'size-large']]],
                    ['attributes' => [['name' => 'color', 'value' => 'green'], ['name' => 'sizes', 'value' => 'size-medium']]],
                    ['attributes' => [['name' => 'color', 'value' => 'green'], ['name' => 'sizes', 'value' => 'size-large']]],
                ],
                'images' => [['src' => 'https://gymdog.fitness/wp-content/uploads/grips.png']],
            ],
            [
                'name' => 'Callus Remover', 'slug' => 'mystery-gadget',
                'type' => 'simple', 'on_sale' => true, 'is_in_stock' => true,
                'description' => '<p>Remover</p>', 'short_description' => '',
                'prices' => ['price' => '1995', 'regular_price' => '2559', 'sale_price' => '1995'],
                'categories' => [['name' => 'Accessories', 'slug' => 'accessories']],
                'attributes' => [], 'variations' => [], 'images' => [],
            ],
        ], 200, ['X-WP-TotalPages' => 1]),
    ]);
}

it('imports products, options, real variant combinations and brand mapping', function () {
    fakeStore();

    $counts = (new WooCommerceImporter('https://gymdog.fitness', rehostMedia: false))->import();

    $grips = Product::where('slug', 'velites-hand-grips-all-terrain')->first();
    expect($grips->brand->name)->toBe('Velites')          // from config brand_map
        ->and($grips->status)->toBe('active')
        ->and($grips->categories->pluck('slug')->sort()->values()->all())->toBe(['fingerless', 'grips'])
        ->and($grips->options->pluck('name')->all())->toBe(['Colour', 'Size'])
        ->and($grips->variants)->toHaveCount(4);           // the 4 real variations

    // Each variant is linked to its colour + size value.
    $variant = $grips->variants->first();
    expect($variant->optionValues->pluck('value')->all())->toContain('Black')
        ->and($variant->price_cents)->toBe(5495)
        ->and($variant->stock_qty)->toBe(10);              // default_stock

    // Colour swatch applied.
    expect($grips->options->firstWhere('name', 'Colour')->values->firstWhere('value', 'Black')->swatch)->not->toBeNull();

    expect($counts['variants'])->toBe(5); // 4 + 1 simple
});

it('captures a sale and nests categories', function () {
    fakeStore();
    (new WooCommerceImporter('https://gymdog.fitness', rehostMedia: false))->import();

    $callus = Product::where('slug', 'mystery-gadget')->first();
    $v = $callus->variants->first();
    expect($v->price_cents)->toBe(1995)
        ->and($v->compare_at_price_cents)->toBe(2559)      // on sale
        ->and($v->isOnSale())->toBeTrue();

    // fingerless nested under grips (config category_parents).
    $fingerless = Category::where('slug', 'fingerless')->first();
    expect($fingerless->parent->slug)->toBe('grips');
});

it('flags products with no mapped brand', function () {
    fakeStore();
    $importer = new WooCommerceImporter('https://gymdog.fitness', rehostMedia: false);
    $importer->import();

    expect(Product::where('slug', 'mystery-gadget')->first()->brand_id)->toBeNull()
        ->and(collect($importer->needsAttention)->contains(fn ($n) => str_contains($n, 'mystery-gadget')))->toBeTrue();
});

it('re-hosts product images into the media library', function () {
    Storage::fake('public');
    // A real (non-trivial) PNG so the 600×600 thumb conversion succeeds.
    $img = imagecreatetruecolor(64, 64);
    imagefilledrectangle($img, 0, 0, 64, 64, imagecolorallocate($img, 200, 100, 50));
    ob_start();
    imagepng($img);
    $png = ob_get_clean();
    imagedestroy($img);
    fakeStore();
    Http::fake(['https://gymdog.fitness/wp-content/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);

    $counts = (new WooCommerceImporter('https://gymdog.fitness'))->import();

    $grips = Product::where('slug', 'velites-hand-grips-all-terrain')->first();
    expect($grips->hasMedia('images'))->toBeTrue()
        ->and($counts['media'])->toBe(1);
});
