@extends('layouts.app')

@section('title', 'GymDog Fitness — Malta\'s CrossFit Portal')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-24 text-center">
        <p class="text-sm uppercase tracking-widest" style="color: var(--brand-accent)">Malta's CrossFit Portal</p>
        <h1 class="mt-4 text-5xl md:text-6xl font-light tracking-tight">
            Gear that keeps up with your <span style="color: var(--brand-primary)">training</span>.
        </h1>
        <p class="mt-6 text-gray-500 max-w-xl mx-auto">
            Grips, ropes, belts and sleeves from the brands Maltese boxes trust.
            Free delivery over &euro;50 across Malta.
        </p>
        <a href="/shop"
           class="mt-10 inline-block rounded-full px-8 py-3 text-sm font-semibold text-gray-900 shadow-sm"
           style="background: var(--brand-secondary)">
            Shop the range
        </a>
    </section>
@endsection
