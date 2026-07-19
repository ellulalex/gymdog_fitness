@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10"
         x-data="productPicker(@js($variantsData), @js($product->options->pluck('name')->all()))">
        <nav class="text-sm text-gray-400 mb-6">
            <a href="/" class="hover:text-gray-700">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('shop') }}" class="hover:text-gray-700">Shop</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            {{-- Gallery --}}
            @php $images = $product->getMedia('images'); @endphp
            <div x-data="{ active: @js($images->first()?->getUrl()) }">
                <div class="aspect-square rounded-2xl bg-gray-50 overflow-hidden flex items-center justify-center">
                    @if ($images->isNotEmpty())
                        <img :src="active" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-7xl font-light text-gray-200">{{ Str::of($product->name)->substr(0, 1)->upper() }}</span>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($images as $img)
                            <button type="button" @click="active = @js($img->getUrl())"
                                    class="h-16 w-16 rounded-lg overflow-hidden border border-gray-200"
                                    :class="active === @js($img->getUrl()) ? 'ring-2 ring-[var(--brand-primary)]' : ''">
                                <img src="{{ $img->getUrl('thumb') }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
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

                {{-- Options --}}
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

                {{-- Quantity + add to cart --}}
                <div class="mt-8 flex items-center gap-4" x-data="{ qty: 1 }">
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button type="button" class="px-3 py-2" @click="qty = Math.max(1, qty - 1)">−</button>
                        <span class="w-10 text-center" x-text="qty"></span>
                        <button type="button" class="px-3 py-2" @click="qty++">+</button>
                    </div>
                    <button type="button" disabled
                            class="flex-1 rounded-full px-6 py-3 text-sm font-semibold text-gray-900 opacity-60 cursor-not-allowed"
                            style="background: var(--brand-secondary)">
                        Add to cart
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-400">Checkout arrives in Phase 2.</p>

                {{-- Accordions --}}
                <div class="mt-8 divide-y divide-gray-100 border-t border-gray-100">
                    <details class="py-3">
                        <summary class="cursor-pointer text-sm font-medium">Delivery &amp; Return</summary>
                        <p class="mt-2 text-sm text-gray-500">Free delivery on orders over €50 across Malta.</p>
                    </details>
                    <details class="py-3">
                        <summary class="cursor-pointer text-sm font-medium">Size Guide</summary>
                        <p class="mt-2 text-sm text-gray-500">See the size guide for measurements.</p>
                    </details>
                </div>

                {{-- Meta --}}
                <dl class="mt-8 text-sm text-gray-500 space-y-1">
                    <div class="flex gap-2"><dt class="w-24 text-gray-400">SKU</dt><dd x-text="sku() || '—'"></dd></div>
                    @if ($product->categories->isNotEmpty())
                        <div class="flex gap-2">
                            <dt class="w-24 text-gray-400">Categories</dt>
                            <dd>{{ $product->categories->pluck('name')->join(', ') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <script>
        function productPicker(variants, optionNames) {
            return {
                variants,
                optionNames,
                selected: {},
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
@endsection
