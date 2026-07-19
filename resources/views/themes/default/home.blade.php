@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-24 text-center">
        <p class="text-sm uppercase tracking-widest text-gray-400">{{ $tenant?->name ?? config('app.name') }}</p>
        <h1 class="mt-4 text-5xl font-light tracking-tight">Welcome</h1>
        <p class="mt-6 text-gray-500 max-w-xl mx-auto">
            This storefront is running on the commerce platform. The catalogue arrives in Phase&nbsp;1.
        </p>
        <a href="/shop"
           class="mt-10 inline-block rounded-full px-8 py-3 text-sm font-semibold text-gray-900"
           style="background: var(--brand-secondary)">
            Browse the shop
        </a>
    </section>
@endsection
