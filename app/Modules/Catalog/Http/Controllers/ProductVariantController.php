<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreProductVariantRequest;
use App\Modules\Catalog\Http\Resources\ProductVariantResource;
use App\Modules\Catalog\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductVariantController
{
    public function store(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $variant = $product->variants()->create($request->validated());

        return ApiResponse::success(new ProductVariantResource($variant), status: 201);
    }

    public function destroy(Product $product, string $variant): JsonResponse
    {
        $product->variants()->where('id', $variant)->firstOrFail()->delete();

        return ApiResponse::success(status: 204);
    }
}
