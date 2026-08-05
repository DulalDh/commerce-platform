<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class StripeGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $publishableKey = Config::get('services.stripe.key');

        // A real integration creates a Checkout Session via stripe/stripe-php
        // and returns its hosted URL; this builds an equivalent redirect
        // shape without the SDK dependency, keeping the gateway offline-testable
        // like the others.
        $redirectUrl = 'https://checkout.stripe.com/pay?'.http_build_query([
            'key' => $publishableKey,
            'client_reference_id' => $payment->idempotency_key,
            'amount' => (int) round($payment->amount * 100),
            'currency' => strtolower($payment->currency),
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.stripe.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('id'),
            idempotencyKey: $request->input('client_reference_id'),
            successful: $request->input('type') === 'checkout.session.completed',
            raw: $request->all(),
        );
    }
}
