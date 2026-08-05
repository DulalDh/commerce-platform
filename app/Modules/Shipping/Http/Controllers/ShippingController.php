<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Http\Requests\CalculateRateRequest;
use App\Modules\Shipping\Http\Requests\CreateShipmentRequest;
use App\Modules\Shipping\Http\Resources\ShipmentResource;
use App\Modules\Shipping\Services\ShippingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShippingController
{
    public function __construct(protected ShippingService $shippingService) {}

    public function calculateRate(CalculateRateRequest $request): JsonResponse
    {
        $rate = $this->shippingService->calculateRate($request->provider, [
            'origin' => $request->origin,
            'destination' => $request->destination,
            'weight_kg' => (float) $request->weight_kg,
        ]);

        return ApiResponse::success($rate);
    }

    public function createShipment(CreateShipmentRequest $request, Order $order): JsonResponse
    {
        $shipment = $this->shippingService->createShipment($order, $request->provider);

        return ApiResponse::success(new ShipmentResource($shipment), status: 201);
    }

    /**
     * Public endpoint - couriers call this directly, no user session.
     */
    public function webhook(Request $request, string $provider): Response
    {
        $this->shippingService->handleWebhook($provider, $request);

        return response('OK', 200);
    }
}
