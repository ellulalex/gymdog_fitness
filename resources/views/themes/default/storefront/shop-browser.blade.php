<div class="mx-auto max-w-6xl px-6 py-10 grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-10">
    {{-- Sidebar filters --}}
    <aside class="space-y-8">
        @if ($categoryTree->isNotEmpty() && ! $categorySlug)
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Categories</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($categoryTree as $root)
                        <li>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="categories" value="{{ $root->slug }}" class="rounded border-gray-300">
                                <span>{{ $root->name }}</span>
                            </label>
                            @if ($root->children->isNotEmpty())
                                <ul class="ml-5 mt-1 space-y-1">
                                    @foreach ($root->children as $child)
                                        <li>
                                            <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                                                <input type="checkbox" wire:model.live="categories" value="{{ $child->slug }}" class="rounded border-gray-300">
                                                <span>{{ $child->name }}</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($brandFacets->isNotEmpty())
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Brands</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($brandFacets as $brand)
                        <li>
                            <label class="flex items-center justify-between gap-2 cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="brands" value="{{ $brand['slug'] }}" class="rounded border-gray-300">
                                    {{ $brand['name'] }}
                                </span>
                                <span class="text-gray-400">{{ $brand['count'] }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($colourFacets->isNotEmpty())
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Colour</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($colourFacets as $colour)
                        <li>
                            <label class="flex items-center justify-between gap-2 cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" wire:model.live="colours" value="{{ $colour['value'] }}" class="rounded border-gray-300">
                                    <span class="inline-block h-4 w-4 rounded-full border border-gray-200" style="background: {{ $colour['swatch'] ?? '#eee' }}"></span>
                                    {{ $colour['value'] }}
                                </span>
                                <span class="text-gray-400">{{ $colour['count'] }}</span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Max price</h2>
            <input type="range" min="10" max="100" step="5" wire:model.live="priceMax" class="w-full accent-[var(--brand-primary)]">
            <p class="text-sm text-gray-500 mt-1">up to €{{ $priceMax ?? 100 }}</p>
        </div>

        <button type="button" wire:click="clearFilters" class="text-sm text-gray-500 underline hover:text-gray-900">Clear filters</button>
    </aside>

    {{-- Results --}}
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4">
            <p class="text-sm text-gray-500">
                {{ $products->total() }} {{ Str::plural('result', $products->total()) }}
            </p>
            <div class="flex items-center gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <span class="text-gray-500">Sort</span>
                    <select wire:model.live="sort" class="rounded-lg border-gray-200 text-sm">
                        <option value="featured">Featured</option>
                        <option value="price_low">Price: low to high</option>
                        <option value="price_high">Price: high to low</option>
                        <option value="newest">Newest</option>
                    </select>
                </label>
                <label class="flex items-center gap-2">
                    <span class="text-gray-500">Show</span>
                    <select wire:model.live="perPage" class="rounded-lg border-gray-200 text-sm">
                        <option value="12">12</option>
                        <option value="24">24</option>
                        <option value="48">48</option>
                    </select>
                </label>
            </div>
        </div>

        @if ($products->isEmpty())
            <p class="py-24 text-center text-gray-500">No products match these filters.</p>
        @else
            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($products as $product)
                    @include('storefront.partials.product-tile', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
