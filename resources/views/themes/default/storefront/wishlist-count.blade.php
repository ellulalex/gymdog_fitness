<a href="{{ route('wishlist') }}" class="relative inline-flex items-center" aria-label="Wishlist">
    <span aria-hidden="true">♡</span>
    @if ($count > 0)
        <span class="absolute -top-2 -right-2 min-w-4 h-4 px-1 rounded-full text-[10px] font-semibold text-white flex items-center justify-center"
              style="background: var(--brand-accent)">{{ $count }}</span>
    @endif
</a>
