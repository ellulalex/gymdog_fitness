<?php

namespace App\Domain\Payments;

use App\Models\Order;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGateway
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createIntent(Order $order): PaymentIntentData
    {
        // Reuse an existing intent so retries don't create duplicates.
        if ($order->stripe_payment_intent_id) {
            $intent = $this->stripe->paymentIntents->retrieve($order->stripe_payment_intent_id);

            return new PaymentIntentData($intent->id, $intent->client_secret);
        }

        $params = [
            'amount' => $order->total_cents,
            'currency' => strtolower($order->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
            'receipt_email' => $order->email,
        ];

        // Connect-ready: if a connected account is configured, route the charge
        // there as a destination charge and take an application fee (spec §7).
        // Empty today → a direct charge on the platform account.
        if ($account = config('services.stripe.connected_account')) {
            $params['transfer_data'] = ['destination' => $account];
            $feeBps = (int) config('services.stripe.application_fee_bps');
            if ($feeBps > 0) {
                $params['application_fee_amount'] = (int) round($order->total_cents * $feeBps / 10000);
            }
        }

        $intent = $this->stripe->paymentIntents->create($params, [
            'idempotency_key' => 'intent_'.$order->number,
        ]);

        $order->update(['stripe_payment_intent_id' => $intent->id]);

        return new PaymentIntentData($intent->id, $intent->client_secret);
    }

    public function refund(Order $order, ?int $amountCents = null): void
    {
        if (! $order->stripe_payment_intent_id) {
            return;
        }

        $this->stripe->refunds->create(array_filter([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => $amountCents,
        ]));
    }

    public function verifyWebhook(string $payload, string $signature): array
    {
        $event = Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));

        return [
            'id' => $event->id,
            'type' => $event->type,
            'data' => $event->data->toArray(),
        ];
    }
}
