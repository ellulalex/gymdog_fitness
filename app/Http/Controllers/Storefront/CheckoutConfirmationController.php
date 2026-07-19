<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CheckoutConfirmationController extends Controller
{
    public function __invoke(Request $request): View
    {
        $order = Order::where('number', $request->query('order'))->firstOrFail();

        // The order is placed; the cart has served its purpose. Payment status
        // is confirmed by webhook, not this redirect (spec §7).
        app(CartManager::class)->current()->lines()->delete();

        return view('storefront.confirmation', [
            'order' => $order->load('lines'),
        ]);
    }
}
