<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Modules\Promotions\Http\Requests\StoreFlashSaleRequest;
use App\Modules\Promotions\Http\Resources\FlashSaleResource;
use App\Modules\Promotions\Models\FlashSale;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FlashSaleController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            FlashSaleResource::collection(FlashSale::with('products')->orderByDesc('starts_at')->get())
        );
    }

    public function store(StoreFlashSaleRequest $request): JsonResponse
    {
        $flashSale = FlashSale::create($request->only(['name', 'discount_percentage', 'starts_at', 'ends_at']));
        $flashSale->products()->sync($request->product_ids);

        return ApiResponse::success(new FlashSaleResource($flashSale->load('products')), status: 201);
    }

    public function show(FlashSale $flashSale): JsonResponse
    {
        return ApiResponse::success(new FlashSaleResource($flashSale->load('products')));
    }

    public function destroy(FlashSale $flashSale): JsonResponse
    {
        $flashSale->delete();

        return ApiResponse::success(status: 204);
    }
}
