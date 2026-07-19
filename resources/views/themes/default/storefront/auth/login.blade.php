@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-3xl font-light tracking-tight">Sign in</h1>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-gray-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border-gray-300">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border-gray-300">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300"> Remember me
            </label>
            <button type="submit" class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white" style="background: var(--brand-primary)">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-500">
            New here? <a href="{{ route('register') }}" class="underline hover:text-[var(--brand-primary)]">Create an account</a>
        </p>
    </div>
@endsection
