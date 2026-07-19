@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-10">
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

            {{-- Purchase box (Livewire) --}}
            <livewire:storefront.add-to-cart :product="$product" />
        </div>
    </div>
@endsection
