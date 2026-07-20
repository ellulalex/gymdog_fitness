@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-3xl font-light tracking-tight">Forgot your password?</h1>
        <p class="mt-3 text-sm text-gray-500">Enter your email and we'll send you a reset link.</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-gray-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border-gray-300">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white" style="background: var(--brand-primary)">
                Email reset link
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-500">
            <a href="{{ route('login') }}" class="underline hover:text-[var(--brand-primary)]">Back to sign in</a>
        </p>
    </div>
@endsection
