<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class NagadGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $merchantId = Config::get('services.nagad.merchant_id');
        $baseUrl = Config::get('services.nagad.api_url', 'https://sandbox.mynagad.com/checkout');

        $redirectUrl = $baseUrl.'?'.http_build_query([
            'merchant_id' => $merchantId,
            'order_id' => $payment->idempotency_key,
            'amount' => $payment->amount,
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.nagad.merchant_private_key');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('payment_ref_id'),
            idempotencyKey: $request->input('order_id'),
            successful: $request->input('status') === 'Success',
            raw: $request->all(),
        );
    }
}
