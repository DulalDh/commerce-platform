<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;

/**
 * No external gateway involved - COD "completes" immediately at initiate()
 * time (no money has actually moved; fulfillment collects it on delivery).
 * PaymentController checks for this by seeing initiate() didn't return a
 * redirect_url, and marks the payment completed synchronously instead of
 * waiting on a webhook that will never arrive.
 */
class CashOnDeliveryGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        return ['requires_redirect' => false, 'auto_complete' => true];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false;
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        throw new \LogicException('Cash on Delivery has no webhook.');
    }
}
