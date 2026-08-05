<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController
{
    public function index(): JsonResponse
    {
        $products = Product::with(['tags', 'variants', 'images'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(
            ProductResource::collection($products),
            ['pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
            ]]
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->safe()->except(['tag_ids', 'variants']));

            if ($request->filled('tag_ids')) {
                $product->tags()->sync($request->input('tag_ids'));
            }

            foreach ($request->input('variants', []) as $variant) {
                $product->variants()->create($variant);
            }

            return $product;
        });

        return ApiResponse::success(
            new ProductResource($product->load(['tags', 'variants', 'images'])),
            status: 201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(
            new ProductResource($product->load(['tags', 'variants', 'images']))
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->safe()->except(['tag_ids']));

        if ($request->has('tag_ids')) {
            $product->tags()->sync($request->input('tag_ids'));
        }

        return ApiResponse::success(
            new ProductResource($product->load(['tags', 'variants', 'images']))
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::success(status: 204);
    }
}
