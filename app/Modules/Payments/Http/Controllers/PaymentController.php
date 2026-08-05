<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Http\Requests\InitiatePaymentRequest;
use App\Modules\Payments\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController
{
    public function __construct(protected PaymentService $paymentService) {}

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->order_id);

        $result = $this->paymentService->initiate($order, $request->gateway);

        return ApiResponse::success($result, status: 201);
    }

    /**
     * Public endpoint (no auth) - gateways call this directly. Always
     * returns 200 on any recognized outcome, including no-ops, so the
     * gateway doesn't keep retrying a webhook we've already handled.
     */
    public function webhook(Request $request, string $gateway): Response
    {
        $this->paymentService->handleWebhook($gateway, $request);

        return response('OK', 200);
    }
}
