<?php

namespace App\Livewire\Storefront;

use App\Support\Wishlist\WishlistManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistCount extends Component
{
    #[On('wishlist-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('storefront.wishlist-count', [
            'count' => app(WishlistManager::class)->count(),
        ]);
    }
}
