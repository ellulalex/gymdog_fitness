<div x-data="productPicker(@js($variantsData), @js($product->options->pluck('name')->all()))">
    @if ($product->brand)
        <a href="{{ route('brand.show', $product->brand->slug) }}"
           class="text-xs uppercase tracking-wide text-gray-400 hover:text-gray-700">{{ $product->brand->name }}</a>
    @endif
    <h1 class="mt-1 text-3xl font-light tracking-tight">{{ $product->name }}</h1>

    <div class="mt-4 flex items-baseline gap-3">
        <span class="text-2xl font-semibold" x-text="formatPrice(price())"></span>
        <template x-if="compareAt()">
            <span class="text-gray-400 line-through" x-text="formatPrice(compareAt())"></span>
        </template>
    </div>

    <p class="mt-2 text-sm" x-text="inStock() ? 'In stock' : 'Out of stock'"
       :class="inStock() ? 'text-green-700' : 'text-red-600'"></p>

    @if ($product->description)
        <p class="mt-6 text-gray-600 leading-relaxed">{{ $product->description }}</p>
    @endif

    @foreach ($product->options as $option)
        <div class="mt-6">
            <p class="text-sm font-medium text-gray-700 mb-2">{{ $option->name }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($option->values as $value)
                    <button type="button"
                            @click="select('{{ $option->name }}', @js($value->value))"
                            :class="selected['{{ $option->name }}'] === @js($value->value) ? 'ring-2 ring-offset-1 ring-[var(--brand-primary)]' : 'border-gray-300'"
                            class="min-w-10 h-10 px-3 rounded-lg border text-sm flex items-center gap-2">
                        @if ($value->swatch)
                            <span class="inline-block h-4 w-4 rounded-full border border-gray-200" style="background: {{ $value->swatch }}"></span>
                        @endif
                        {{ $value->value }}
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="mt-8 flex items-center gap-4">
        <div class="flex items-center border border-gray-300 rounded-lg">
            <button type="button" class="px-3 py-2" @click="qty = Math.max(1, qty - 1)">−</button>
            <span class="w-10 text-center" x-text="qty"></span>
            <button type="button" class="px-3 py-2" @click="qty++">+</button>
        </div>
        <button type="button"
                @click="$wire.add(current()?.id, qty)"
                :disabled="!inStock()"
                :class="inStock() ? '' : 'opacity-50 cursor-not-allowed'"
                class="flex-1 rounded-full px-6 py-3 text-sm font-semibold text-gray-900"
                style="background: var(--brand-secondary)"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="add">Add to cart</span>
            <span wire:loading wire:target="add">Adding…</span>
        </button>
    </div>

    @if ($added)
        <p class="mt-3 text-sm text-green-700">
            Added to cart — <a href="{{ route('cart') }}" class="underline">view cart</a>
        </p>
    @endif

    <div class="mt-8 divide-y divide-gray-100 border-t border-gray-100">
        <details class="py-3">
            <summary class="cursor-pointer text-sm font-medium">Delivery &amp; Return</summary>
            <p class="mt-2 text-sm text-gray-500">Free delivery on orders over €50 across Malta; €10 flat rate below that.</p>
        </details>
        <details class="py-3">
            <summary class="cursor-pointer text-sm font-medium">Size Guide</summary>
            <p class="mt-2 text-sm text-gray-500">See the size guide for measurements.</p>
        </details>
    </div>

    <dl class="mt-8 text-sm text-gray-500 space-y-1">
        <div class="flex gap-2"><dt class="w-24 text-gray-400">SKU</dt><dd x-text="sku() || '—'"></dd></div>
        @if ($product->categories->isNotEmpty())
            <div class="flex gap-2">
                <dt class="w-24 text-gray-400">Categories</dt>
                <dd>{{ $product->categories->pluck('name')->join(', ') }}</dd>
            </div>
        @endif
    </dl>

    <script>
        function productPicker(variants, optionNames) {
            return {
                variants,
                optionNames,
                selected: {},
                qty: 1,
                init() {
                    const first = this.variants.find(v => v.stock > 0) || this.variants[0];
                    if (first) this.selected = { ...first.options };
                },
                select(name, value) { this.selected[name] = value; },
                current() {
                    return this.variants.find(v =>
                        this.optionNames.every(n => v.options[n] === this.selected[n])
                    ) || (this.variants.length === 1 ? this.variants[0] : null);
                },
                price() {
                    const c = this.current();
                    return c ? c.price : Math.min(...this.variants.map(v => v.price));
                },
                compareAt() {
                    const c = this.current();
                    return c && c.compare && c.compare > c.price ? c.compare : null;
                },
                inStock() {
                    const c = this.current();
                    return c ? c.stock > 0 : this.variants.some(v => v.stock > 0);
                },
                sku() { return this.current()?.sku; },
                formatPrice(cents) { return '€' + (cents / 100).toFixed(2); },
            };
        }
    </script>
</div>
