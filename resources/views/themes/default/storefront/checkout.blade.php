<div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-10">
    <div>
        @if ($step === 'details')
            <h2 class="text-lg font-semibold mb-4">Contact &amp; delivery</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Full name</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Address</label>
                    <input type="text" wire:model="line1" class="w-full rounded-lg border-gray-300">
                    @error('line1') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Town / City</label>
                        <input type="text" wire:model="city" class="w-full rounded-lg border-gray-300">
                        @error('city') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Postcode</label>
                        <input type="text" wire:model="postcode" class="w-full rounded-lg border-gray-300">
                        @error('postcode') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-sm text-gray-500">Delivery to Malta only.</p>
                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled"
                        class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white"
                        style="background: var(--brand-primary)">
                    <span wire:loading.remove wire:target="placeOrder">Continue to payment</span>
                    <span wire:loading wire:target="placeOrder">Preparing…</span>
                </button>
            </div>
        @else
            <h2 class="text-lg font-semibold mb-4">Payment</h2>
            @if ($stripeKey)
                <div wire:ignore
                     x-data="stripePayment(@js($stripeKey), @js($clientSecret), @js($returnUrl))"
                     x-init="mount()">
                    <div id="payment-element" class="min-h-[120px]"></div>
                    <p x-show="error" x-text="error" class="text-sm text-red-600 mt-2"></p>
                    <button type="button" @click="pay()" :disabled="loading"
                            class="mt-4 w-full rounded-full px-6 py-3 text-sm font-semibold text-white"
                            style="background: var(--brand-primary)">
                        <span x-show="!loading">Pay €{{ number_format($totals->totalCents / 100, 2) }}</span>
                        <span x-show="loading">Processing…</span>
                    </button>
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Stripe keys are not configured. Order <strong>{{ $orderNumber }}</strong> was created as
                    pending — set <code>STRIPE_KEY</code> / <code>STRIPE_SECRET</code> in <code>.env</code> to take payment.
                </div>
            @endif
        @endif
    </div>

    {{-- Summary --}}
    <aside class="h-fit rounded-2xl border border-gray-100 p-6 space-y-3">
        <h2 class="font-semibold">Order summary</h2>
        @foreach ($cart->lines as $line)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $line->variant->product->name }} × {{ $line->qty }}</span>
                <span>€{{ number_format($line->lineTotalCents() / 100, 2) }}</span>
            </div>
        @endforeach
        <div class="border-t border-gray-100 pt-3 flex justify-between text-sm">
            <span class="text-gray-500">Subtotal</span>
            <span>€{{ number_format($totals->subtotalCents / 100, 2) }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-500">Shipping</span>
            <span>{{ $totals->shippingCents === 0 ? 'Free' : '€'.number_format($totals->shippingCents / 100, 2) }}</span>
        </div>
        <div class="flex justify-between font-semibold text-lg border-t border-gray-100 pt-3">
            <span>Total</span>
            <span>€{{ number_format($totals->totalCents / 100, 2) }}</span>
        </div>
        <p class="text-xs text-gray-400">Incl. €{{ number_format($totals->taxCents / 100, 2) }} VAT.</p>
    </aside>
</div>
