<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SslcommerzGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        $storeId = Config::get('services.sslcommerz.store_id');
        $baseUrl = Config::get('services.sslcommerz.api_url', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php');

        $redirectUrl = $baseUrl.'?'.http_build_query([
            'store_id' => $storeId,
            'tran_id' => $payment->idempotency_key,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'success_url' => route('payments.webhook', ['gateway' => 'sslcommerz']),
        ]);

        return ['redirect_url' => $redirectUrl];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.sslcommerz.store_password');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        return new WebhookPayload(
            gatewayReference: $request->input('bank_tran_id'),
            idempotencyKey: $request->input('tran_id'),
            successful: $request->input('status') === 'VALID',
            raw: $request->all(),
        );
    }
}
