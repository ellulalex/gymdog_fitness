@php
    $brand = $tenant?->name ?? config('app.name');
    // Staging serves the same content as production. Without this, Google can
    // index both and the copy competes with the real site. noindex (not a
    // robots.txt Disallow) is the right tool: a blocked crawler never reads the
    // noindex, so disallowing would preserve any indexing already in place.
    $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);
    $isCanonicalHost = $canonicalHost === null || request()->getHost() === $canonicalHost;
    $palette = $tenant?->setting('palette', []) ?? [];
    $primary = $palette['primary'] ?? '#1f3d2b';   // forest green
    $secondary = $palette['secondary'] ?? '#d8c3a5'; // warm tan
    $accent = $palette['accent'] ?? '#c85c6b';       // muted pink/red
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand)</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @unless ($isCanonicalHost)
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="canonical" href="{{ url()->current() }}">
    @stack('head')
    <style>
        :root {
            --brand-primary: {{ $primary }};
            --brand-secondary: {{ $secondary }};
            --brand-accent: {{ $accent }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('storefront.partials.analytics')
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-white text-gray-900 antialiased">
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100" x-data="{ mobileOpen: false }">
        <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button type="button" class="md:hidden -ml-1 p-1 text-gray-600" @click="mobileOpen = ! mobileOpen" aria-label="Menu">
                    <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" style="display:none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600 uppercase tracking-wide">
                    <a href="/" class="hover:text-[var(--brand-primary)]">Home</a>
                    <a href="/shop" class="hover:text-[var(--brand-primary)]">Shop</a>
                    <a href="/blog" class="hover:text-[var(--brand-primary)]">Blog</a>
                    @if (! empty($navGuides) && $navGuides->isNotEmpty())
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <button type="button" @click="open = !open" class="flex items-center gap-1 uppercase hover:text-[var(--brand-primary)]">
                                CrossFit <span class="text-[10px]">▾</span>
                            </button>
                            <div x-show="open" x-transition style="display:none" class="absolute left-0 top-full pt-2 w-60 z-40">
                                <div class="bg-white border border-gray-100 rounded-lg shadow-lg py-2">
                                    @foreach ($navGuides as $guide)
                                        <a href="{{ url('/'.$guide->slug) }}"
                                           class="block px-4 py-2 text-sm normal-case tracking-normal text-gray-700 hover:bg-gray-50 hover:text-[var(--brand-primary)]">{{ $guide->title }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </nav>
            </div>
            <a href="/" class="text-xl font-semibold tracking-tight">{{ $brand }}</a>
            <div class="flex items-center gap-4 text-gray-600">
                <a href="{{ route('search') }}" aria-label="Search">🔍</a>
                @auth('customer')
                    <a href="{{ route('account') }}" aria-label="Account">👤</a>
                @else
                    <a href="{{ route('login') }}" aria-label="Account">👤</a>
                @endauth
                <livewire:storefront.wishlist-count />
                <livewire:storefront.cart-count />
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="mobileOpen" x-transition style="display:none" class="md:hidden border-t border-gray-100 bg-white">
            <nav class="px-6 py-4 flex flex-col gap-3 text-sm uppercase tracking-wide text-gray-700">
                <a href="/">Home</a>
                <a href="/shop">Shop</a>
                <a href="/blog">Blog</a>
                @if (! empty($navGuides) && $navGuides->isNotEmpty())
                    <p class="pt-2 text-xs text-gray-400">CrossFit</p>
                    @foreach ($navGuides as $guide)
                        <a href="{{ url('/'.$guide->slug) }}" class="pl-3 normal-case tracking-normal text-gray-600">{{ $guide->title }}</a>
                    @endforeach
                @endif
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-24 text-white" style="background: var(--brand-primary)">
        <div class="mx-auto max-w-6xl px-6 py-12 text-sm flex flex-col md:flex-row justify-between gap-4">
            <span>&copy; {{ now()->year }} {{ $brand }}</span>
            <span class="opacity-80">Built on the Gymdog commerce platform</span>
        </div>
    </footer>
    @include('storefront.partials.consent-banner')
    @livewireScripts
</body>
</html>
