@extends('layouts.app')

@section('title', 'Choose a new password')

@section('content')
    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-3xl font-light tracking-tight">Choose a new password</h1>

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required class="w-full rounded-lg border-gray-300">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">New password</label>
                <input type="password" name="password" required autofocus class="w-full rounded-lg border-gray-300">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border-gray-300">
            </div>
            <button type="submit" class="w-full rounded-full px-6 py-3 text-sm font-semibold text-white" style="background: var(--brand-primary)">
                Reset password
            </button>
        </form>
    </div>
@endsection
