@extends('layouts.app')

@section('title', $heading)

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-32 text-center">
        <h1 class="text-4xl font-light tracking-tight">{{ $heading }}</h1>
        <p class="mt-6 text-gray-500">Coming soon.</p>
    </section>
@endsection
