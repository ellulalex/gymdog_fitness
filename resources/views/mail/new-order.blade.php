@php
    $ship = $order->shipping_address ?? [];
@endphp
<x-mail::message>
# New order — €{{ number_format($order->total_cents / 100, 2) }}

**{{ $order->number }}** · paid {{ optional($order->placed_at ?? $order->updated_at)->format('d M Y, H:i') }}

<x-mail::table>
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->lines as $line)
| {{ $line->name_snapshot }} | {{ $line->qty }} | €{{ number_format($line->total_cents / 100, 2) }} |
@endforeach
</x-mail::table>

**Subtotal:** €{{ number_format($order->subtotal_cents / 100, 2) }}
@if ($order->discount_cents > 0)
**Discount:** −€{{ number_format($order->discount_cents / 100, 2) }}@if ($order->discount_code) ({{ $order->discount_code }})@endif
@endif
**Shipping:** {{ $order->shipping_cents === 0 ? 'Free' : '€'.number_format($order->shipping_cents / 100, 2) }}
**Total:** €{{ number_format($order->total_cents / 100, 2) }} _(incl. €{{ number_format($order->tax_cents / 100, 2) }} VAT)_

## Ship to

{{ $ship['name'] ?? '—' }}
{{ $ship['line1'] ?? '' }}
{{ $ship['city'] ?? '' }} {{ $ship['postcode'] ?? '' }}
{{ $ship['country'] ?? '' }}

**Customer:** {{ $order->email }} _(reply to this email to reach them)_

@if ($order->stripe_payment_intent_id)
**Stripe:** `{{ $order->stripe_payment_intent_id }}`
@endif

<x-mail::button :url="url('/admin/orders/'.$order->id)">
View order in admin
</x-mail::button>

GymDog Fitness
</x-mail::message>
