@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@if ($post->meta_description)
    @section('meta_description', $post->meta_description)
@endif

@push('head')
    @include('storefront.partials.article-seo', ['post' => $post])
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-6 py-16">
        @if ($post->type === 'guide')
            <p class="text-xs uppercase tracking-widest" style="color: var(--brand-accent)">CrossFit guide</p>
        @endif
        <h1 class="mt-2 text-4xl font-light tracking-tight">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-gray-400">
            @if ($post->author){{ $post->author->name }} · @endif
            @if ($post->published_at){{ $post->published_at->format('d M Y') }}@endif
        </p>
        @if ($post->categories->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($post->categories as $category)
                    <a href="{{ route('post-category.show', $category->slug) }}"
                       class="text-xs rounded-full px-3 py-1 border border-gray-200 text-gray-600 hover:text-[var(--brand-primary)]">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif
        @if ($post->featured_image)
            <figure class="mt-8">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}"
                     class="w-full rounded-xl object-cover" loading="lazy">
                @if ($post->featured_image_credit)
                    <figcaption class="mt-2 text-xs text-gray-400">{{ $post->featured_image_credit }}</figcaption>
                @endif
            </figure>
        @endif

        <div class="prose prose-neutral mt-8 max-w-none">
            {!! $post->body !!}
        </div>

        @if (! empty($post->faq))
            <section class="mt-12 border-t border-gray-100 pt-8">
                <h2 class="text-2xl font-light tracking-tight">Frequently asked questions</h2>
                <div class="mt-4 divide-y divide-gray-100">
                    @foreach ($post->faq as $item)
                        <details class="py-3">
                            <summary class="cursor-pointer font-medium">{{ $item['question'] ?? '' }}</summary>
                            <p class="mt-2 text-gray-600">{{ $item['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
