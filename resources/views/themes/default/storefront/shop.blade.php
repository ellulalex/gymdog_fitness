@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="mx-auto max-w-6xl px-6 pt-10">
        <nav class="text-sm text-gray-400">
            <a href="/" class="hover:text-gray-700">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('shop') }}" class="hover:text-gray-700">Shop</a>
            @isset($crumb)
                <span class="mx-1">/</span>
                <span class="text-gray-700">{{ $crumb }}</span>
            @endisset
        </nav>
        <h1 class="mt-3 text-3xl font-light tracking-tight">{{ $title }}</h1>
    </div>

    <livewire:storefront.shop-browser
        :category-slug="$categorySlug ?? null"
        :brand-slug="$brandSlug ?? null" />
@endsection
