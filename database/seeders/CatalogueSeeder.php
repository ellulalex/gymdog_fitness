<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\VariantOption;
use App\Models\VariantOptionValue;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Development-only sample catalogue for the gymdog tenant, drawn from the live
 * site audit. NOT wired into DatabaseSeeder — the production seed stays tenant
 * + admin only (real products are re-entered by hand). Run locally with:
 *
 *   php artisan db:seed --class=CatalogueSeeder
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'gymdog')->firstOrFail();
        app(TenantManager::class)->set($tenant); // so tenant_id auto-fills

        // --- Categories (two levels, per the audit) ---
        $cats = [];
        foreach (['grips' => 'Grips', 'belts' => 'Belts', 'knee-sleeves' => 'Knee Sleeves', 'accessories' => 'Accessories', 'jump-ropes' => 'Jump Ropes'] as $slug => $name) {
            $cats[$slug] = Category::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => $slug], ['name' => $name]);
        }
        $cats['fingerless'] = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'fingerless'],
            ['name' => 'Fingerless', 'parent_id' => $cats['grips']->id]
        );
        $cats['wrist-straps'] = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'wrist-straps'],
            ['name' => 'Wrist Straps', 'parent_id' => $cats['accessories']->id]
        );

        // --- Brands ---
        $brands = [];
        foreach (['reyllen' => 'Reyllen', 'picsil' => 'Picsil', 'domyos' => 'Domyos', 'velites' => 'Velites'] as $slug => $name) {
            $brands[$slug] = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => $slug], ['name' => $name]);
        }

        // --- A variable product: colour + size (the Velites grips) ---
        $grips = $this->product('velites-hand-grips-all-terrain', 'Velites Hand Grips All Terrain', $brands['velites'], [$cats['grips']]);
        $colour = $this->option($grips, 'Colour', ['Black' => '#111111', 'Blue' => '#1e40af', 'Pink' => '#c85c6b']);
        $size = $this->option($grips, 'Size', ['M' => null, 'L' => null, 'XL' => null]);
        foreach (['Black', 'Blue', 'Pink'] as $c) {
            foreach (['M', 'L', 'XL'] as $s) {
                $variant = ProductVariant::create([
                    'product_id' => $grips->id,
                    'sku' => 'VEL-'.strtoupper(substr($c, 0, 2)).'-'.$s,
                    'name' => "$c / $s",
                    'price_cents' => 5495,
                    'compare_at_price_cents' => $c === 'Pink' ? 6495 : null, // one colour on sale
                    'stock_qty' => $s === 'XL' ? 0 : 8,
                ]);
                $variant->optionValues()->attach([$colour[$c]->id, $size[$s]->id]);
            }
        }

        // --- Simple products (single variant each) ---
        $this->simple('speed-skipping-rope', 'Speed Skipping Rope', null, [$cats['jump-ropes']], 999, 12);
        $this->simple('jump-rope-abs-b', 'Jump Rope ABS-B', null, [$cats['jump-ropes']], 1450, 20);
        $this->simple('lumbar-belt', 'Lumbar Belt', null, [$cats['belts']], 3495, 0);      // out of stock
        $this->simple('reyllen-gx-belt', 'Reyllen GX Belt', $brands['reyllen'], [$cats['belts']], 3995, 5);
        $this->simple('hex-tech-knee-pads-5mm', 'Hex Tech Knee Pads 5mm', $brands['picsil'], [$cats['knee-sleeves']], 4495, 6, 4995);
        $this->simple('condor-grips', 'Condor Grips', $brands['reyllen'], [$cats['grips'], $cats['fingerless']], 2995, 10);
    }

    private function product(string $slug, string $name, ?Brand $brand, array $categories): Product
    {
        $product = Product::updateOrCreate(
            ['tenant_id' => app(TenantManager::class)->id(), 'slug' => $slug],
            [
                'name' => $name,
                'brand_id' => $brand?->id,
                'description' => "$name — trusted by Maltese boxes.",
                'status' => 'active',
                'published_at' => now(),
            ]
        );
        $product->categories()->sync(collect($categories)->pluck('id'));

        return $product;
    }

    /** @param array<string,?string> $values value => swatch hex */
    private function option(Product $product, string $name, array $values): array
    {
        $option = VariantOption::create(['product_id' => $product->id, 'name' => $name]);
        $out = [];
        $pos = 0;
        foreach ($values as $value => $swatch) {
            $out[$value] = VariantOptionValue::create([
                'variant_option_id' => $option->id,
                'value' => $value,
                'swatch' => $swatch,
                'position' => $pos++,
            ]);
        }

        return $out;
    }

    private function simple(string $slug, string $name, ?Brand $brand, array $categories, int $price, int $stock, ?int $compareAt = null): void
    {
        $product = $this->product($slug, $name, $brand, $categories);
        ProductVariant::updateOrCreate(
            ['product_id' => $product->id, 'sku' => Str::upper(Str::slug($slug))],
            [
                'price_cents' => $price,
                'compare_at_price_cents' => $compareAt,
                'stock_qty' => $stock,
            ]
        );
    }
}
