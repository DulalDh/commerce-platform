<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Http\Requests\CheckoutRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Services\CartService;
use App\Modules\Orders\Services\CheckoutService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CheckoutController
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user()->id);

        try {
            $order = $this->checkoutService->checkout(
                $cart, $request->shipping_address, $request->billing_address, $request->user()->id, $request->coupon_code
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(new OrderResource($order), status: 201);
    }
}
