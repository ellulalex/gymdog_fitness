<?php

namespace App\Domain\Payments;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Webhooks are the source of truth for payment state — never the browser
 * redirect (spec §7). This processor is idempotent: Stripe delivers
 * at-least-once, so a repeated event id is ignored.
 */
class WebhookProcessor
{
    public function process(array $event): void
    {
        // Reject duplicates. The unique index on event_id is the backstop.
        if (StripeEvent::where('event_id', $event['id'])->exists()) {
            return;
        }

        StripeEvent::create([
            'event_id' => $event['id'],
            'type' => $event['type'],
            'processed_at' => now(),
        ]);

        match ($event['type']) {
            'payment_intent.succeeded' => $this->handleSucceeded($event['data']['object'] ?? []),
            'payment_intent.payment_failed' => $this->markStatus($event['data']['object']['id'] ?? null, 'failed'),
            'charge.refunded' => $this->markStatus($event['data']['object']['payment_intent'] ?? null, 'refunded'),
            default => null,
        };
    }

    protected function handleSucceeded(array $intent): void
    {
        $order = $this->orderForIntent($intent['id'] ?? null);

        if (! $order || $order->isPaid()) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->markPaid();

            // Draw down stock and record cost of goods (FIFO across
            // consignments) only once payment is confirmed.
            foreach ($order->lines as $line) {
                if (! $line->variant) {
                    continue;
                }

                $line->variant->decrement('stock_qty', $line->qty);
                $line->update(['cost_cents' => $line->variant->drawDownFifo($line->qty)]);
            }
        });

        Mail::to($order->email)->send(new OrderConfirmation($order));
    }

    protected function markStatus(?string $intentId, string $status): void
    {
        $this->orderForIntent($intentId)?->update(['payment_status' => $status]);
    }

    /** Webhooks have no tenant context, so bypass the tenant scope. */
    protected function orderForIntent(?string $intentId): ?Order
    {
        if (! $intentId) {
            return null;
        }

        return Order::withoutGlobalScopes()
            ->with('lines.variant')
            ->where('stripe_payment_intent_id', $intentId)
            ->first();
    }
}
