<div class="mx-auto max-w-6xl px-6 py-12">
    <h1 class="text-3xl font-light tracking-tight mb-8">Wishlist</h1>

    @if ($products->isEmpty())
        <div class="py-20 text-center">
            <p class="text-gray-500">Your wishlist is empty.</p>
            <a href="{{ route('shop') }}" class="mt-6 inline-block rounded-full px-6 py-3 text-sm font-semibold text-gray-900" style="background: var(--brand-secondary)">Browse the shop</a>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($products as $product)
                <div wire:key="wish-{{ $product->id }}" class="relative">
                    <button type="button" wire:click="remove({{ $product->id }})"
                            class="absolute top-2 right-2 z-10 h-8 w-8 rounded-full bg-white/90 border border-gray-200 text-gray-500 hover:text-red-500"
                            aria-label="Remove from wishlist">✕</button>
                    @include('storefront.partials.product-tile', ['product' => $product])
                </div>
            @endforeach
        </div>
    @endif
</div>
