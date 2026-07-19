<?php

namespace App\Livewire\Storefront;

use App\Models\CartLine;
use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CartPage extends Component
{
    public string $code = '';

    public ?string $discountError = null;

    public function applyCode(): void
    {
        $cart = app(CartManager::class)->current();

        if ($cart->applyCode($this->code)) {
            $this->code = '';
            $this->discountError = null;
            $this->dispatch('cart-updated');
        } else {
            $this->discountError = 'That code isn’t valid for this cart.';
        }
    }

    public function removeDiscount(): void
    {
        app(CartManager::class)->current()->removeDiscount();
        $this->discountError = null;
        $this->dispatch('cart-updated');
    }

    public function increment(int $lineId): void
    {
        if ($line = $this->line($lineId)) {
            $line->increment('qty');
            $this->dispatch('cart-updated');
        }
    }

    public function decrement(int $lineId): void
    {
        if ($line = $this->line($lineId)) {
            if ($line->qty <= 1) {
                $line->delete();
            } else {
                $line->decrement('qty');
            }
            $this->dispatch('cart-updated');
        }
    }

    public function remove(int $lineId): void
    {
        $this->line($lineId)?->delete();
        $this->dispatch('cart-updated');
    }

    /** Only lines belonging to the current cart can be touched. */
    protected function line(int $lineId): ?CartLine
    {
        return app(CartManager::class)->current()->lines()->whereKey($lineId)->first();
    }

    public function render(): View
    {
        $cart = app(CartManager::class)->current()->load('lines.variant.product.brand');

        return view('storefront.cart-page', [
            'cart' => $cart,
            'totals' => $cart->totals(),
            'appliedCode' => $cart->appliedDiscount()?->code,
        ]);
    }
}
