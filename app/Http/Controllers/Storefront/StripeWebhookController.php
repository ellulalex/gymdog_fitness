<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Payments\PaymentGateway;
use App\Domain\Payments\WebhookProcessor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, WebhookProcessor $processor): Response
    {
        try {
            $event = $gateway->verifyWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
            );
        } catch (Throwable) {
            // Bad signature or malformed payload — Stripe will retry.
            return response('invalid signature', 400);
        }

        $processor->process($event);

        return response('ok', 200);
    }
}
