@extends('layouts.app')

@section('title', 'Order confirmed')

@section('content')
    <div class="mx-auto max-w-2xl px-6 py-20 text-center">
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-3xl font-light tracking-tight">Thank you</h1>
        <p class="mt-3 text-gray-500">
            Your order <strong>{{ $order->number }}</strong> has been placed. A confirmation
            email is on its way to {{ $order->email }} once payment clears.
        </p>

        <div class="mt-10 text-left rounded-2xl border border-gray-100 p-6">
            <h2 class="font-semibold mb-4">Order summary</h2>
            @foreach ($order->lines as $line)
                <div class="flex justify-between text-sm py-1">
                    <span class="text-gray-600">{{ $line->name_snapshot }} × {{ $line->qty }}</span>
                    <span>€{{ number_format($line->total_cents / 100, 2) }}</span>
                </div>
            @endforeach
            <div class="mt-3 border-t border-gray-100 pt-3 flex justify-between text-sm">
                <span class="text-gray-500">Shipping</span>
                <span>{{ $order->shipping_cents === 0 ? 'Free' : '€'.number_format($order->shipping_cents / 100, 2) }}</span>
            </div>
            <div class="mt-1 flex justify-between font-semibold">
                <span>Total</span>
                <span>€{{ number_format($order->total_cents / 100, 2) }}</span>
            </div>
        </div>

        <a href="{{ route('shop') }}" class="mt-10 inline-block rounded-full px-6 py-3 text-sm font-semibold text-gray-900" style="background: var(--brand-secondary)">
            Continue shopping
        </a>
    </div>
@endsection
