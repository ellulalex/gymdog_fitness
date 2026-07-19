<?php

namespace App\Livewire\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariantOptionValue;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The shop listing with faceted filtering. Drives /shop, category pages and
 * brand pages — the fixed context (categorySlug / brandSlug) is set by the
 * route, the rest are user-selectable filters kept in the URL.
 *
 * Facet counts reflect the fixed context + price, but not the user's own
 * selections in that same dimension, so ticking one colour doesn't zero out
 * the others (standard faceted-search behaviour).
 */
class ShopBrowser extends Component
{
    use WithPagination;

    /** Fixed context from the route (not user-editable). */
    public ?string $categorySlug = null;

    public ?string $brandSlug = null;

    #[Url(as: 'cat')]
    public array $categories = [];

    #[Url(as: 'brand')]
    public array $brands = [];

    #[Url(as: 'colour')]
    public array $colours = [];

    #[Url(as: 'max')]
    public ?int $priceMax = null;

    #[Url]
    public string $sort = 'featured';

    #[Url]
    public int $perPage = 12;

    public function updating($name): void
    {
        // Any filter change resets pagination.
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['categories', 'brands', 'colours', 'priceMax']);
        $this->resetPage();
    }

    // --- Query building -----------------------------------------------------

    /** Active products constrained by the fixed route context only. */
    protected function contextQuery(): Builder
    {
        return Product::query()
            ->active()
            ->when($this->contextCategoryIds(), fn (Builder $q, array $ids) => $q->whereHas(
                'categories', fn (Builder $c) => $c->whereIn('categories.id', $ids)
            ))
            ->when($this->brandSlug, fn (Builder $q, string $slug) => $q->whereHas(
                'brand', fn (Builder $b) => $b->where('slug', $slug)
            ));
    }

    /** The category ids in scope: the route category plus its descendants. */
    protected function contextCategoryIds(): array
    {
        if (! $this->categorySlug) {
            return [];
        }

        $category = Category::where('slug', $this->categorySlug)->first();

        if (! $category) {
            return [];
        }

        return collect([$category->id])
            ->merge($category->children->pluck('id'))
            ->all();
    }

    /** Context + all user selections — the actual listing. */
    protected function listingQuery(): Builder
    {
        return $this->contextQuery()
            ->when($this->categories, fn (Builder $q, array $slugs) => $q->whereHas(
                'categories', fn (Builder $c) => $c->whereIn('categories.slug', $slugs)
            ))
            ->when($this->brands, fn (Builder $q, array $slugs) => $q->whereHas(
                'brand', fn (Builder $b) => $b->whereIn('slug', $slugs)
            ))
            ->when($this->colours, fn (Builder $q, array $values) => $q->whereHas(
                'variants.optionValues', fn (Builder $ov) => $ov
                    ->whereIn('value', $values)
                    ->whereHas('option', fn (Builder $o) => $o->where('name', 'Colour'))
            ))
            ->when($this->priceMax, fn (Builder $q, int $max) => $q->whereHas(
                'variants', fn (Builder $v) => $v->where('price_cents', '<=', $max * 100)
            ));
    }

    // --- Facets -------------------------------------------------------------

    protected function brandFacets(): Collection
    {
        if ($this->brandSlug) {
            return collect(); // brand is fixed on a brand page
        }

        return Brand::query()->orderBy('name')->get()
            ->map(fn (Brand $brand) => [
                'slug' => $brand->slug,
                'name' => $brand->name,
                'count' => (clone $this->contextQuery())->where('brand_id', $brand->id)->count(),
            ])
            ->filter(fn ($b) => $b['count'] > 0)
            ->values();
    }

    protected function colourFacets(): Collection
    {
        $colours = VariantOptionValue::query()
            ->whereHas('option', fn (Builder $o) => $o->where('name', 'Colour'))
            ->get()
            ->unique('value');

        return $colours->map(fn ($c) => [
            'value' => $c->value,
            'swatch' => $c->swatch,
            'count' => (clone $this->contextQuery())->whereHas(
                'variants.optionValues', fn (Builder $ov) => $ov
                    ->where('value', $c->value)
                    ->whereHas('option', fn (Builder $o) => $o->where('name', 'Colour'))
            )->count(),
        ])
            ->filter(fn ($c) => $c['count'] > 0)
            ->sortByDesc('count')
            ->values();
    }

    protected function categoryTree(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        $query = $this->listingQuery();

        match ($this->sort) {
            'price_low' => $query->withMin('variants', 'price_cents')->orderBy('variants_min_price_cents'),
            'price_high' => $query->withMin('variants', 'price_cents')->orderByDesc('variants_min_price_cents'),
            'newest' => $query->orderByDesc('published_at'),
            default => $query->orderBy('name'),
        };

        $products = $query
            ->with(['brand', 'variants'])
            ->paginate($this->perPage);

        return view('storefront.shop-browser', [
            'products' => $products,
            'brandFacets' => $this->brandFacets(),
            'colourFacets' => $this->colourFacets(),
            'categoryTree' => $this->categoryTree(),
        ]);
    }
}
