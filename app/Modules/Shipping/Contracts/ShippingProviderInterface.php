<?php

namespace App\Modules\Shipping\Contracts;

use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;

/**
 * Common contract every courier adapter implements, mirroring
 * PaymentGatewayInterface - adding a new provider means writing one new
 * class, not touching ShippingController.
 */
interface ShippingProviderInterface
{
    /**
     * Quote a delivery rate without creating a shipment - used for the
     * rate-calculation endpoint at checkout time.
     *
     * @param  array{origin: string, destination: string, weight_kg: float}  $params
     * @return array{rate: float, currency: string, eta_days: int}
     */
    public function calculateRate(array $params): array;

    /**
     * Book the shipment with the courier for a confirmed order, returning
     * the tracking number the courier will reference in status webhooks.
     *
     * @return array{tracking_number: string, rate: float, currency: string}
     */
    public function createShipment(Order $order): array;

    public function verifyWebhookSignature(Request $request): bool;

    public function parseWebhookPayload(Request $request): ShippingWebhookPayload;
}
