<?php

namespace App\Livewire\Storefront;

use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-renders the badge when a line is added/changed elsewhere.
    }

    public function render(): View
    {
        return view('storefront.cart-count', [
            'count' => app(CartManager::class)->current()->itemCount(),
        ]);
    }
}
