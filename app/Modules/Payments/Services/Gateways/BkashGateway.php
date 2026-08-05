<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class BkashGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $appKey = Config::get('services.bkash.app_key');
        $baseUrl = Config::get('services.bkash.api_url', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/checkout');

        $redirectUrl = $baseUrl.'/create?'.http_build_query([
            'app_key' => $appKey,
            'invoice' => $payment->idempotency_key,
            'amount' => $payment->amount,
            'callback_url' => route('payments.webhook', ['gateway' => 'bkash']),
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.bkash.app_secret');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('trxID'),
            idempotencyKey: $request->input('invoice'),
            successful: $request->input('transactionStatus') === 'Completed',
            raw: $request->all(),
        );
    }
}
