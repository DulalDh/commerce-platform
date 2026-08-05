<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Http\Requests\UpdateOrderStatusRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStatusMachine;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController
{
    public function __construct(protected OrderStatusMachine $statusMachine) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->when($request->user()->tenant_id === null, fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::success(new OrderResource($order->load('items', 'statusHistories')));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->statusMachine->transition(
                $order, $request->status, $request->note, $request->user()->id
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(new OrderResource($order->load('items')));
    }
}
