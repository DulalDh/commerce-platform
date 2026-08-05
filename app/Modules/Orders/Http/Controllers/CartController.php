<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Http\Requests\AddCartItemRequest;
use App\Modules\Orders\Http\Requests\UpdateCartItemRequest;
use App\Modules\Orders\Http\Resources\CartResource;
use App\Modules\Orders\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController
{
    public function __construct(protected CartService $cartService) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user()->id);

        return ApiResponse::success(new CartResource($cart->load('items')));
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user()->id);
        $cart = $this->cartService->addItem($cart, $request->product_id, $request->variant_id, $request->quantity);

        return ApiResponse::success(new CartResource($cart), status: 201);
    }

    public function updateItem(UpdateCartItemRequest $request, string $item): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user()->id);
        $cart = $this->cartService->updateItemQuantity($cart, $item, $request->quantity);

        return ApiResponse::success(new CartResource($cart));
    }

    public function removeItem(Request $request, string $item): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user()->id);
        $cart = $this->cartService->removeItem($cart, $item);

        return ApiResponse::success(new CartResource($cart));
    }
}
