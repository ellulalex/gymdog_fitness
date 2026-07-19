<?php

namespace App\Domain\Catalogue;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use App\Models\VariantOptionValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports products from a live WooCommerce Store API (`/wp-json/wc/store/v1`,
 * public — no keys). Idempotent: each product upserts on slug and its options,
 * variants and media are rebuilt from source.
 *
 * The public API gives prices, sale prices, stock status, categories, images,
 * option terms and the real variation combinations. It does NOT expose
 * per-variant price/stock/SKU or brands — see $needsAttention and
 * config/catalogue.php.
 */
class WooCommerceImporter
{
    /** @var array<string,int> */
    public array $counts = [
        'products' => 0, 'variants' => 0, 'categories' => 0,
        'brands' => 0, 'media' => 0,
    ];

    /** @var array<int,string> notes for the operator (missing brand, etc.) */
    public array $needsAttention = [];

    public function __construct(
        private readonly string $baseUrl,
        private readonly bool $rehostMedia = true,
        private readonly string $status = 'active',
    ) {}

    public function import(): array
    {
        foreach ($this->fetchProducts() as $wc) {
            $this->importProduct($wc);
            $this->counts['products']++;
        }

        return $this->counts;
    }

    private function importProduct(array $wc): void
    {
        $slug = $wc['slug'];

        $product = Product::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $this->plain($wc['name']),
                'description' => $wc['description'] ?? '',
                'meta_description' => Str::limit($this->plain($wc['short_description'] ?? ''), 160, ''),
                'brand_id' => $this->brandId($slug),
                'status' => $this->status,
                'published_at' => now(),
            ],
        );

        $this->syncCategories($product, $wc['categories'] ?? []);

        // Rebuild options + variants from source for a clean re-run.
        $product->options()->delete();
        $product->variants()->delete();

        $optionValues = $this->buildOptions($product, $wc['attributes'] ?? []);
        $this->buildVariants($product, $wc, $optionValues);

        if ($this->rehostMedia) {
            $this->syncMedia($product, $wc['images'] ?? []);
        }
    }

    private function brandId(string $slug): ?int
    {
        $name = config('catalogue.woocommerce.brand_map')[$slug] ?? null;

        if (! $name) {
            $this->needsAttention[] = "No brand mapped for '{$slug}'.";

            return null;
        }

        $brand = Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        $this->counts['brands'] += $brand->wasRecentlyCreated ? 1 : 0;

        return $brand->id;
    }

    /** @param  array<int,array>  $categories */
    private function syncCategories(Product $product, array $categories): void
    {
        $parents = config('catalogue.woocommerce.category_parents');
        $ids = [];

        foreach ($categories as $cat) {
            $parentId = null;
            if (isset($parents[$cat['slug']])) {
                $parent = Category::firstOrCreate(
                    ['slug' => $parents[$cat['slug']]],
                    ['name' => Str::headline($parents[$cat['slug']])],
                );
                $this->counts['categories'] += $parent->wasRecentlyCreated ? 1 : 0;
                $parentId = $parent->id;
            }

            $category = Category::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $this->plain($cat['name']), 'parent_id' => $parentId],
            );
            $this->counts['categories'] += $category->wasRecentlyCreated ? 1 : 0;
            $ids[] = $category->id;
        }

        $product->categories()->sync($ids);
    }

    /**
     * @param  array<int,array>  $attributes
     * @return array<string,array<string,int>> attribute name => (term slug => option value id)
     */
    private function buildOptions(Product $product, array $attributes): array
    {
        $names = config('catalogue.woocommerce.attribute_names');
        $swatches = config('catalogue.woocommerce.colour_swatches');
        $map = [];

        foreach ($attributes as $position => $attr) {
            $ourName = $names[$attr['name']] ?? Str::headline($attr['name']);
            $option = VariantOption::create([
                'product_id' => $product->id,
                'name' => $ourName,
                'position' => $position,
            ]);

            foreach ($attr['terms'] ?? [] as $i => $term) {
                $value = VariantOptionValue::create([
                    'variant_option_id' => $option->id,
                    'value' => $term['name'],
                    'swatch' => $ourName === 'Colour' ? ($swatches[$term['name']] ?? null) : null,
                    'position' => $i,
                ]);
                $map[$attr['name']][$term['slug']] = $value->id;
            }
        }

        return $map;
    }

    /** @param  array<string,array<string,int>>  $optionValues */
    private function buildVariants(Product $product, array $wc, array $optionValues): void
    {
        $prices = $wc['prices'] ?? [];
        $price = (int) ($prices['price'] ?? 0);
        $regular = (int) ($prices['regular_price'] ?? $price);
        $compareAt = ($wc['on_sale'] ?? false) && $regular > $price ? $regular : null;
        $stock = ($wc['is_in_stock'] ?? true) ? (int) config('catalogue.woocommerce.default_stock') : 0;

        $variations = $wc['variations'] ?? [];

        if ($variations === []) {
            $this->createVariant($product, null, $price, $compareAt, $stock, []);

            return;
        }

        foreach ($variations as $variation) {
            $valueIds = [];
            $names = [];

            foreach ($variation['attributes'] ?? [] as $attr) {
                $valueId = $optionValues[$attr['name']][$attr['value']] ?? null;
                if ($valueId) {
                    $valueIds[] = $valueId;
                    $names[] = VariantOptionValue::whereKey($valueId)->value('value');
                }
            }

            $this->createVariant($product, implode(' / ', $names) ?: null, $price, $compareAt, $stock, $valueIds);
        }

        if ($price === 0) {
            $this->needsAttention[] = "Zero price on '{$product->slug}' — set it manually.";
        }
    }

    /** @param  int[]  $valueIds */
    private function createVariant(Product $product, ?string $name, int $price, ?int $compareAt, int $stock, array $valueIds): void
    {
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => $name,
            'price_cents' => $price,
            'compare_at_price_cents' => $compareAt,
            'stock_qty' => $stock,
        ]);

        if ($valueIds) {
            $variant->optionValues()->attach($valueIds);
        }

        $this->counts['variants']++;
    }

    /** @param  array<int,array>  $images */
    private function syncMedia(Product $product, array $images): void
    {
        $product->clearMediaCollection('images');

        foreach ($images as $image) {
            $src = $image['src'] ?? null;
            if (! $src) {
                continue;
            }

            try {
                $response = Http::get($src);
                if (! $response->successful()) {
                    continue;
                }

                $name = basename(parse_url($src, PHP_URL_PATH) ?? 'image.jpg');
                $product->addMediaFromString($response->body())
                    ->usingFileName($name)
                    ->toMediaCollection('images');
                $this->counts['media']++;
            } catch (Throwable) {
                // Skip a broken image rather than fail the whole import.
            }
        }
    }

    /** @return array<int,array> */
    private function fetchProducts(): array
    {
        $items = [];
        $page = 1;

        do {
            $response = Http::acceptJson()->get(
                "{$this->baseUrl}/wp-json/wc/store/v1/products",
                ['per_page' => 100, 'page' => $page],
            );

            if ($response->status() === 400) {
                break;
            }

            $response->throw();
            $batch = $response->json();

            if (! is_array($batch) || $batch === []) {
                break;
            }

            $items = array_merge($items, $batch);
            $total = (int) $response->header('X-WP-TotalPages') ?: 1;
            $page++;
        } while ($page <= $total);

        return $items;
    }

    private function plain(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }
}
