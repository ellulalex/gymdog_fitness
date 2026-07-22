@extends('layouts.app')

@section('title', 'GymDog Fitness — Malta\'s CrossFit Portal')
@section('meta_description', 'CrossFit grips, ropes, belts and knee sleeves from the brands Maltese boxes trust. Free delivery over €50 across Malta.')

@section('content')
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-16 text-center">
        <p class="text-sm uppercase tracking-widest" style="color: var(--brand-accent)">Malta's CrossFit Portal</p>
        <h1 class="mt-4 text-5xl md:text-6xl font-light tracking-tight">
            Gear that keeps up with your <span style="color: var(--brand-primary)">training</span>.
        </h1>
        <p class="mt-6 text-gray-500 max-w-xl mx-auto">
            Grips, ropes, belts and sleeves from the brands Maltese boxes trust.
            Free delivery over &euro;50 across Malta.
        </p>
        <a href="{{ route('shop') }}"
           class="mt-10 inline-block rounded-full px-8 py-3 text-sm font-semibold text-gray-900 shadow-sm"
           style="background: var(--brand-secondary)">
            Shop the range
        </a>
    </section>

    {{-- Trust badges --}}
    <section class="border-y border-gray-100 bg-gray-50/50">
        <div class="mx-auto max-w-6xl px-6 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center text-sm">
            @foreach ([
                ['🏷️', 'Amazing value every day'],
                ['💬', 'Friendly customer service'],
                ['💳', 'All payment methods'],
                ['🚚', 'Free delivery over €50 (Malta)'],
            ] as [$icon, $label])
                <div>
                    <div class="text-2xl">{{ $icon }}</div>
                    <p class="mt-2 text-gray-600">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Featured products --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-6xl px-6 py-16">
            <div class="flex items-end justify-between mb-8">
                <h2 class="text-2xl font-light tracking-tight">Shop the range</h2>
                <a href="{{ route('shop') }}" class="text-sm text-gray-500 hover:text-[var(--brand-primary)]">View all →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($featured as $product)
                    @include('storefront.partials.product-tile', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Promo banner --}}
    @if ($promo)
        @php
            $promoLabel = $promo->type === 'fixed'
                ? '€'.number_format($promo->value / 100, 2).' off'
                : $promo->value.'% off';
        @endphp
        <section class="mx-auto max-w-6xl px-6 pb-16">
            <div class="rounded-2xl px-8 py-10 text-center" style="background: var(--brand-secondary)">
                <h2 class="text-2xl font-medium text-gray-900">{{ $promoLabel }} your first order</h2>
                <p class="mt-2 text-gray-700">Use this code at checkout.</p>
                <div class="mt-5 inline-flex items-center gap-3" x-data="{ copied: false }">
                    <code class="rounded-lg bg-white/70 px-4 py-2 font-mono text-lg tracking-wider">{{ $promo->code }}</code>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $promo->code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="text-sm underline text-gray-800">
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" style="display:none">Copied!</span>
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- Latest articles --}}
    @if ($articles->isNotEmpty())
        <section class="mx-auto max-w-6xl px-6 pb-20">
            <div class="flex items-end justify-between mb-8">
                <h2 class="text-2xl font-light tracking-tight">Latest articles</h2>
                <a href="{{ route('blog') }}" class="text-sm text-gray-500 hover:text-[var(--brand-primary)]">Read the blog →</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($articles as $post)
                    <a href="{{ url('/'.$post->slug) }}" class="block group">
                        @if ($post->featured_image)
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                                 class="aspect-video w-full rounded-xl object-cover mb-3" loading="lazy">
                        @else
                            <div class="aspect-video rounded-xl bg-gray-50 mb-3"></div>
                        @endif
                        <p class="text-xs text-gray-400">{{ optional($post->published_at)->format('d M Y') }}</p>
                        <h3 class="mt-1 font-medium group-hover:text-[var(--brand-primary)]">{{ $post->title }}</h3>
                        @if ($post->excerpt)
                            <p class="mt-1 text-sm text-gray-500 line-clamp-3">{{ $post->excerpt }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
