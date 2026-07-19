<?php

namespace App\Domain\Payments;

use App\Models\Order;

/**
 * Payment provider seam. The app talks to this, never to Stripe directly, so
 * the storefront/checkout stay provider-agnostic and tests use a fake.
 */
interface PaymentGateway
{
    /** Create (or reuse) a payment intent for an order and return its client secret. */
    public function createIntent(Order $order): PaymentIntentData;

    public function refund(Order $order, ?int $amountCents = null): void;

    /**
     * Verify a webhook signature and return the event as an array.
     *
     * @return array{id:string, type:string, data:array}
     */
    public function verifyWebhook(string $payload, string $signature): array;
}
