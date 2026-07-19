<?php

namespace App\Support\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Resolves the current guest cart, keyed by a token kept in the session. When
 * customer accounts arrive, a logged-in customer's cart is resolved by
 * customer_id instead — this is the one place that changes.
 */
class CartManager
{
    protected ?Cart $cart = null;

    public function current(): Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        $token = Session::get('cart_token');

        if (! $token) {
            $token = (string) Str::uuid();
            Session::put('cart_token', $token);
        }

        return $this->cart = Cart::firstOrCreate(
            ['session_token' => $token],
            ['currency' => 'EUR'],
        );
    }

    public function forget(): void
    {
        $this->cart = null;
    }
}
