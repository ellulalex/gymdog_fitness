<?php

namespace App\Livewire\Storefront;

use App\Support\Wishlist\WishlistManager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WishlistPage extends Component
{
    public function remove(int $productId): void
    {
        app(WishlistManager::class)->toggle($productId); // toggle off
        $this->dispatch('wishlist-updated');
    }

    public function render(): View
    {
        return view('storefront.wishlist-page', [
            'products' => app(WishlistManager::class)->products(),
        ]);
    }
}
