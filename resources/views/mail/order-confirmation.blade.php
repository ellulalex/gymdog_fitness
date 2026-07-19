<x-mail::message>
# Thanks for your order

Hi, we've received your order **{{ $order->number }}** and payment is confirmed.

<x-mail::table>
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->lines as $line)
| {{ $line->name_snapshot }} | {{ $line->qty }} | €{{ number_format($line->total_cents / 100, 2) }} |
@endforeach
</x-mail::table>

**Subtotal:** €{{ number_format($order->subtotal_cents / 100, 2) }}
@if ($order->discount_cents > 0)
**Discount:** −€{{ number_format($order->discount_cents / 100, 2) }}
@endif
**Shipping:** {{ $order->shipping_cents === 0 ? 'Free' : '€'.number_format($order->shipping_cents / 100, 2) }}
**Total:** €{{ number_format($order->total_cents / 100, 2) }} _(incl. €{{ number_format($order->tax_cents / 100, 2) }} VAT)_

We'll let you know when it ships.

Thanks,<br>
GymDog Fitness
</x-mail::message>
