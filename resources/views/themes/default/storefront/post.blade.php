@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@if ($post->meta_description)
    @section('meta_description', $post->meta_description)
@endif

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
        <div class="prose prose-neutral mt-8 max-w-none">
            {!! $post->body !!}
        </div>
    </article>
@endsection
