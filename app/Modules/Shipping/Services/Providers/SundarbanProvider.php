<?php

namespace App\Modules\Shipping\Services\Providers;

use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Contracts\ShippingWebhookPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class SundarbanProvider implements ShippingProviderInterface
{
    public function calculateRate(array $params): array
    {
        $base = 70.0;
        $perKg = 18.0;

        return [
            'rate' => round($base + $perKg * $params['weight_kg'], 2),
            'currency' => 'BDT',
            'eta_days' => 4,
        ];
    }

    public function createShipment(Order $order): array
    {
        return [
            'tracking_number' => 'SUNDARBAN-'.strtoupper(Str::random(10)),
            'rate' => 70.0,
            'currency' => 'BDT',
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = Config::get('services.sundarban.api_secret');
        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        return hash_equals($expected, (string) $request->header('X-Signature'));
    }

    public function parseWebhookPayload(Request $request): ShippingWebhookPayload
    {
        return new ShippingWebhookPayload(
            trackingNumber: $request->input('booking_id'),
            status: $request->input('status'),
            raw: $request->all(),
        );
    }
}
