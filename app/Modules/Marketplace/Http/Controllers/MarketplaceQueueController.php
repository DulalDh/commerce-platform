<?php

namespace App\Modules\Marketplace\Http\Controllers;

use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketplace\Http\Requests\RejectMarketplaceListingRequest;
use App\Modules\Marketplace\Jobs\IndexProductJob;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MarketplaceQueueController
{
    public function index(): JsonResponse
    {
        $products = Product::withoutGlobalScopes()
            ->where('marketplace_status', 'pending')
            ->with(['category', 'brand'])
            ->orderBy('created_at')
            ->paginate(20);

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function approve(Product $product): JsonResponse
    {
        $product->update([
            'marketplace_status' => 'approved',
            'published_to_marketplace' => true,
            'marketplace_rejection_reason' => null,
        ]);

        IndexProductJob::dispatch((string) $product->id);

        return ApiResponse::success(new ProductResource($product));
    }

    public function reject(RejectMarketplaceListingRequest $request, Product $product): JsonResponse
    {
        $product->update([
            'marketplace_status' => 'rejected',
            'published_to_marketplace' => false,
            'marketplace_rejection_reason' => $request->reason,
        ]);

        IndexProductJob::dispatch((string) $product->id);

        return ApiResponse::success(new ProductResource($product));
    }
}
