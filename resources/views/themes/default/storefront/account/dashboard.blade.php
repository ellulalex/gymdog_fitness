@extends('layouts.app')

@section('title', 'My account')

@section('content')
    <div class="mx-auto max-w-4xl px-6 py-12">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-light tracking-tight">Hi, {{ $customer->name }}</h1>
                <p class="text-sm text-gray-500">{{ $customer->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 underline hover:text-gray-900">Sign out</button>
            </form>
        </div>

        <h2 class="mt-12 text-sm font-semibold uppercase tracking-wide text-gray-500">Order history</h2>

        @if ($orders->isEmpty())
            <p class="mt-4 text-gray-500">No orders yet. <a href="{{ route('shop') }}" class="underline">Start shopping</a>.</p>
        @else
            <div class="mt-4 divide-y divide-gray-100 border-y border-gray-100">
                @foreach ($orders as $order)
                    <div class="py-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="font-medium">{{ $order->number }}</p>
                            <p class="text-sm text-gray-500">
                                {{ optional($order->placed_at)->format('d M Y') ?? 'Pending' }} ·
                                {{ $order->lines->sum('qty') }} {{ Str::plural('item', $order->lines->sum('qty')) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">€{{ number_format($order->total_cents / 100, 2) }}</p>
                            <p class="text-xs uppercase tracking-wide text-gray-400">{{ $order->payment_status }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
