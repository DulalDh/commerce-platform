<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class RocketGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $merchantId = Config::get('services.rocket.merchant_id');
        $baseUrl = Config::get('services.rocket.api_url', 'https://sandbox.rocket.com.bd/checkout');

        $redirectUrl = $baseUrl.'?'.http_build_query([
            'merchant_id' => $merchantId,
            'invoice' => $payment->idempotency_key,
            'amount' => $payment->amount,
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.rocket.merchant_secret');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('transaction_id'),
            idempotencyKey: $request->input('invoice'),
            successful: $request->input('status') === 'completed',
            raw: $request->all(),
        );
    }
}
