<div class="mx-auto max-w-6xl px-6 py-10">
    <h1 class="text-3xl font-light tracking-tight mb-6">Search</h1>

    <div class="relative max-w-xl">
        <input type="search" wire:model.live.debounce.300ms="q" autofocus
               placeholder="Search products, guides and articles…"
               class="w-full rounded-full border-gray-300 pl-5 pr-10 py-3">
        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400" wire:loading>…</span>
    </div>

    @if (mb_strlen($term) >= 2)
        @if ($products->isEmpty() && $content->isEmpty())
            <p class="py-16 text-gray-500">No results for “{{ $term }}”.</p>
        @else
            @if ($products->isNotEmpty())
                <h2 class="mt-10 text-sm font-semibold uppercase tracking-wide text-gray-500">Products</h2>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($products as $product)
                        @include('storefront.partials.product-tile', ['product' => $product])
                    @endforeach
                </div>
            @endif

            @if ($content->isNotEmpty())
                <h2 class="mt-10 text-sm font-semibold uppercase tracking-wide text-gray-500">Guides &amp; articles</h2>
                <ul class="mt-4 divide-y divide-gray-100 border-y border-gray-100">
                    @foreach ($content as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="flex items-center justify-between py-3 hover:text-[var(--brand-primary)]">
                                <span>{{ $item['title'] }}</span>
                                <span class="text-xs text-gray-400">{{ $item['kind'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    @else
        <p class="mt-6 text-gray-400 text-sm">Type at least two characters.</p>
    @endif
</div>
