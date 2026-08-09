@extends('layouts.app')

@section('title', 'Order confirmed')

@if (config('analytics.ga4_id'))
    {{--
        GA4 ecommerce purchase event — this is what ties revenue back to the
        content that earned it. Respects Consent Mode: with consent denied it
        still sends a cookieless ping rather than nothing. transaction_id is the
        order number, so GA4 dedupes if the page is refreshed.
    --}}
    @push('head')
        @php
            // Built here rather than inline: Blade's @json directive can't parse
            // a multi-line closure (it splits on the commas inside).
            $gaItems = $order->lines->map(fn ($line) => [
                'item_id' => $line->sku_snapshot ?: (string) $line->product_variant_id,
                'item_name' => $line->name_snapshot,
                'price' => round($line->unit_price_cents / 100, 2),
                'quantity' => $line->qty,
            ])->values();
        @endphp
        <script>
            window.dataLayer = window.dataLayer || [];
            window.addEventListener('load', function () {
                if (typeof gtag !== 'function') return;
                gtag('event', 'purchase', {
                    transaction_id: @json($order->number),
                    value: {{ round($order->total_cents / 100, 2) }},
                    tax: {{ round($order->tax_cents / 100, 2) }},
                    shipping: {{ round($order->shipping_cents / 100, 2) }},
                    currency: @json($order->currency ?? 'EUR'),
                    items: @json($gaItems)
                });
            });
        </script>
    @endpush
@endif

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
