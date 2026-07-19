@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <div class="mx-auto max-w-6xl px-6 py-12">
        <h1 class="text-3xl font-light tracking-tight mb-8">Checkout</h1>
        <livewire:storefront.checkout />
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        function stripePayment(publishableKey, clientSecret, returnUrl) {
            return {
                stripe: null,
                elements: null,
                loading: false,
                error: '',
                mount() {
                    if (!publishableKey || !clientSecret) return;
                    this.stripe = Stripe(publishableKey);
                    this.elements = this.stripe.elements({ clientSecret });
                    this.elements.create('payment').mount('#payment-element');
                },
                async pay() {
                    if (!this.stripe) return;
                    this.loading = true;
                    this.error = '';
                    const { error } = await this.stripe.confirmPayment({
                        elements: this.elements,
                        confirmParams: { return_url: returnUrl },
                    });
                    // On success Stripe redirects to return_url. Errors land here.
                    if (error) {
                        this.error = error.message;
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endsection
