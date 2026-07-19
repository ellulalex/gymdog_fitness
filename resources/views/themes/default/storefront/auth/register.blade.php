@extends('layouts.app')

@section('title', 'Create an account')

@section('content')
    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-3xl font-light tracking-tight">Create an account</h1>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label class="block text-sm text-gray-600 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full rounded-lg border-gray-300">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border-gray-300">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border-gray-300">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border-gray-300">
            </div>
            <button type="submit" class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white" style="background: var(--brand-primary)">
                Create account
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-500">
            Already have an account? <a href="{{ route('login') }}" class="underline hover:text-[var(--brand-primary)]">Sign in</a>
        </p>
    </div>
@endsection
