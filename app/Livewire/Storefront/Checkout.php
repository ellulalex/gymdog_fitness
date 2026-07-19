<?php

namespace App\Livewire\Storefront;

use App\Domain\Orders\OrderBuilder;
use App\Domain\Payments\PaymentGateway;
use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Checkout extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $line1 = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:20')]
    public string $postcode = '';

    public string $country = 'MT';

    public string $step = 'details';

    public ?string $clientSecret = null;

    public ?string $orderNumber = null;

    public function mount()
    {
        if (app(CartManager::class)->current()->load('lines')->isEmpty()) {
            return redirect()->route('shop');
        }
    }

    public function placeOrder(OrderBuilder $builder, PaymentGateway $gateway)
    {
        $this->validate();

        $cart = app(CartManager::class)->current()->load('lines.variant.product');

        if ($cart->isEmpty()) {
            return redirect()->route('shop');
        }

        $address = [
            'name' => $this->name,
            'line1' => $this->line1,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'country' => $this->country,
        ];

        $order = $builder->fromCart($cart, [
            'email' => $this->email,
            'shipping_address' => $address,
            'billing_address' => $address,
        ]);

        $intent = $gateway->createIntent($order);

        $this->orderNumber = $order->number;
        $this->clientSecret = $intent->clientSecret;
        $this->step = 'pay';
    }

    public function render(): View
    {
        $cart = app(CartManager::class)->current()->load('lines.variant.product');

        return view('storefront.checkout', [
            'cart' => $cart,
            'totals' => $cart->totals(),
            'stripeKey' => config('services.stripe.key'),
            'returnUrl' => $this->orderNumber ? route('checkout.confirmation', ['order' => $this->orderNumber]) : null,
        ]);
    }
}
