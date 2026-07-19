<a href="{{ route('cart') }}" class="relative inline-flex items-center" aria-label="Cart">
    <span aria-hidden="true">🛒</span>
    @if ($count > 0)
        <span class="absolute -top-2 -right-2 min-w-4 h-4 px-1 rounded-full text-[10px] font-semibold text-white flex items-center justify-center"
              style="background: var(--brand-primary)">{{ $count }}</span>
    @endif
</a>
