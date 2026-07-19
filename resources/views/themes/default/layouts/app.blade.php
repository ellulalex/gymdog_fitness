@php
    $brand = $tenant?->name ?? config('app.name');
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
    <style>
        :root {
            --brand-primary: {{ $primary }};
            --brand-secondary: {{ $secondary }};
            --brand-accent: {{ $accent }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-white text-gray-900 antialiased">
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100">
        <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
            <nav class="hidden md:flex gap-6 text-sm font-medium text-gray-600 uppercase tracking-wide">
                <a href="/" class="hover:text-[var(--brand-primary)]">Home</a>
                <a href="/shop" class="hover:text-[var(--brand-primary)]">Shop</a>
                <a href="/blog" class="hover:text-[var(--brand-primary)]">Blog</a>
            </nav>
            <a href="/" class="text-xl font-semibold tracking-tight">{{ $brand }}</a>
            <div class="flex items-center gap-4 text-gray-600">
                <span aria-hidden="true">🔍</span>
                <livewire:storefront.wishlist-count />
                <livewire:storefront.cart-count />
            </div>
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
    @livewireScripts
</body>
</html>
