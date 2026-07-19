@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@if ($page->meta_description)
    @section('meta_description', $page->meta_description)
@endif

@section('content')
    <article class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="text-4xl font-light tracking-tight">{{ $page->title }}</h1>
        <div class="prose prose-neutral mt-8 max-w-none">
            {!! $page->body !!}
        </div>
    </article>
@endsection
