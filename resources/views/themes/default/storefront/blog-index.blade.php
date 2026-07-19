@extends('layouts.app')

@section('title', $title ?? 'Blog')

@section('content')
    <div class="mx-auto max-w-5xl px-6 py-16">
        <h1 class="text-4xl font-light tracking-tight">{{ $title ?? 'Blog' }}</h1>

        @if ($categories->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('blog') }}" class="text-sm rounded-full px-3 py-1 border border-gray-200 hover:bg-gray-50">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route('post-category.show', $category->slug) }}"
                       class="text-sm rounded-full px-3 py-1 border border-gray-200 hover:bg-gray-50">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <p class="py-20 text-center text-gray-500">No articles yet.</p>
        @else
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($posts as $post)
                    <a href="{{ url('/'.$post->slug) }}" class="block group">
                        <div class="aspect-video rounded-xl bg-gray-50 mb-3"></div>
                        <p class="text-xs text-gray-400">
                            @if ($post->published_at){{ $post->published_at->format('d M Y') }}@endif
                        </p>
                        <h2 class="mt-1 font-medium group-hover:text-[var(--brand-primary)]">{{ $post->title }}</h2>
                        @if ($post->excerpt)
                            <p class="mt-1 text-sm text-gray-500 line-clamp-3">{{ $post->excerpt }}</p>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
