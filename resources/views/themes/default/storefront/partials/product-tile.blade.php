@php
    $lowest = $product->lowestPriceCents();
    $onSale = $product->isOnSale();
    $inStock = $product->inStock();
    $saleVariant = $onSale ? $product->variants->firstWhere(fn ($v) => $v->isOnSale()) : null;
@endphp
<a href="{{ route('product.show', $product->slug) }}"
   class="group block rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow bg-white">
    <div class="relative aspect-square bg-gray-50 flex items-center justify-center">
        {{-- Media library lands as a follow-up; placeholder for now. --}}
        <span class="text-4xl font-light text-gray-300">{{ Str::of($product->name)->substr(0, 1)->upper() }}</span>
        <div class="absolute top-3 left-3 flex flex-col gap-1">
            @if ($onSale)
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: var(--brand-accent)">
                    Sale{{ $saleVariant?->discountPercent() ? ' −'.$saleVariant->discountPercent().'%' : '' }}
                </span>
            @endif
            @unless ($inStock)
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-800 text-white">Out of stock</span>
            @endunless
        </div>
    </div>
    <div class="p-4">
        @if ($product->brand)
            <p class="text-xs uppercase tracking-wide text-gray-400">{{ $product->brand->name }}</p>
        @endif
        <h3 class="mt-1 text-sm font-medium text-gray-900 group-hover:text-[var(--brand-primary)]">{{ $product->name }}</h3>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="font-semibold">€{{ number_format(($lowest ?? 0) / 100, 2) }}</span>
            @if ($onSale && $saleVariant?->compare_at_price_cents)
                <span class="text-sm text-gray-400 line-through">€{{ number_format($saleVariant->compare_at_price_cents / 100, 2) }}</span>
            @endif
        </div>
    </div>
</a>
