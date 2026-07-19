<div class="mx-auto max-w-6xl px-6 py-12">
    <h1 class="text-3xl font-light tracking-tight mb-8">Your cart</h1>

    @if ($cart->isEmpty())
        <div class="py-20 text-center">
            <p class="text-gray-500">Your cart is empty.</p>
            <a href="{{ route('shop') }}" class="mt-6 inline-block rounded-full px-6 py-3 text-sm font-semibold text-gray-900" style="background: var(--brand-secondary)">Browse the shop</a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-10">
            {{-- Lines --}}
            <div class="divide-y divide-gray-100 border-y border-gray-100">
                @foreach ($cart->lines as $line)
                    @php $variant = $line->variant; @endphp
                    <div class="flex items-center gap-4 py-4" wire:key="line-{{ $line->id }}">
                        <div class="h-16 w-16 rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                            @if ($url = $variant?->product?->imageUrl('thumb'))
                                <img src="{{ $url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="text-xl text-gray-300">{{ Str::of($variant?->product?->name ?? '?')->substr(0, 1)->upper() }}</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('product.show', $variant->product->slug) }}" class="font-medium hover:text-[var(--brand-primary)]">{{ $variant->product->name }}</a>
                            @if ($variant->name)
                                <p class="text-sm text-gray-500">{{ $variant->name }}</p>
                            @endif
                            <p class="text-sm text-gray-500">€{{ number_format($variant->price_cents / 100, 2) }}</p>
                        </div>
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button type="button" class="px-3 py-1.5" wire:click="decrement({{ $line->id }})">−</button>
                            <span class="w-8 text-center text-sm">{{ $line->qty }}</span>
                            <button type="button" class="px-3 py-1.5" wire:click="increment({{ $line->id }})">+</button>
                        </div>
                        <div class="w-20 text-right font-medium">€{{ number_format($line->lineTotalCents() / 100, 2) }}</div>
                        <button type="button" class="text-gray-300 hover:text-red-500" wire:click="remove({{ $line->id }})" aria-label="Remove">✕</button>
                    </div>
                @endforeach
            </div>

            {{-- Summary --}}
            <aside class="h-fit rounded-2xl border border-gray-100 p-6 space-y-3">
                <h2 class="font-semibold">Order summary</h2>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span>€{{ number_format($totals->subtotalCents / 100, 2) }}</span>
                </div>
                @if ($totals->discountCents > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Discount</span>
                        <span>−€{{ number_format($totals->discountCents / 100, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Shipping</span>
                    <span>{{ $totals->shippingCents === 0 ? 'Free' : '€'.number_format($totals->shippingCents / 100, 2) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-lg border-t border-gray-100 pt-3">
                    <span>Total</span>
                    <span>€{{ number_format($totals->totalCents / 100, 2) }}</span>
                </div>
                <p class="text-xs text-gray-400">Includes €{{ number_format($totals->taxCents / 100, 2) }} VAT. Free delivery over €50 (Malta).</p>

                <button type="button" disabled
                        class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white opacity-60 cursor-not-allowed"
                        style="background: var(--brand-primary)">
                    Checkout
                </button>
                <p class="text-center text-xs text-gray-400">Stripe checkout lands next.</p>
            </aside>
        </div>
    @endif
</div>
