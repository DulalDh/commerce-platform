<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PaypalGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $clientId = Config::get('services.paypal.client_id');
        $baseUrl = Config::get('services.paypal.api_url', 'https://www.sandbox.paypal.com/checkoutnow');

        $redirectUrl = $baseUrl.'?'.http_build_query([
            'client-id' => $clientId,
            'custom_id' => $payment->idempotency_key,
            'amount' => $payment->amount,
            'currency_code' => $payment->currency,
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.paypal.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('resource.id'),
            idempotencyKey: $request->input('resource.custom_id'),
            successful: $request->input('event_type') === 'CHECKOUT.ORDER.APPROVED',
            raw: $request->all(),
        );
    }
}
