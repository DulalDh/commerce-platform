<?php

namespace App\Modules\Marketplace\Http\Controllers;

use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductPublishController
{
    public function store(Product $product): JsonResponse
    {
        $product->update([
            'marketplace_status' => 'pending',
            'marketplace_rejection_reason' => null,
        ]);

        return ApiResponse::success(new ProductResource($product));
    }
}
