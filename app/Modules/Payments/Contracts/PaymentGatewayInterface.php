<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;

/**
 * Common contract every payment gateway implements, so checkout/webhook
 * handling code never branches on which gateway is in use - adding a new
 * gateway means writing one new class, not touching PaymentController.
 */
interface PaymentGatewayInterface
{
    /**
     * Start a payment for the given order. Returns data the client needs to
     * complete payment (e.g. a redirect_url for hosted checkout gateways,
     * or immediate confirmation for gateways like COD that need no redirect).
     */
    public function initiate(Order $order, Payment $payment): array;

    /**
     * Verify the webhook request actually came from this gateway (signature/
     * checksum check) before any of its payload is trusted.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Extract the gateway's transaction reference and outcome from a
     * verified webhook payload.
     */
    public function parseWebhookPayload(Request $request): WebhookPayload;
}
