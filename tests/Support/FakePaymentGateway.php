<?php

namespace Tests\Support;

use App\Domain\Payments\PaymentGateway;
use App\Domain\Payments\PaymentIntentData;
use App\Models\Order;

/**
 * In-memory PaymentGateway for tests — no Stripe keys or network required.
 * verifyWebhook just decodes the JSON payload (signature already trusted).
 */
class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int,array{order:int, amount:?int}> */
    public array $refunds = [];

    public function createIntent(Order $order): PaymentIntentData
    {
        $id = 'pi_fake_'.$order->id;
        $order->update(['stripe_payment_intent_id' => $id]);

        return new PaymentIntentData($id, $id.'_secret');
    }

    public function refund(Order $order, ?int $amountCents = null): void
    {
        $this->refunds[] = ['order' => $order->id, 'amount' => $amountCents];
    }

    public function verifyWebhook(string $payload, string $signature): array
    {
        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
