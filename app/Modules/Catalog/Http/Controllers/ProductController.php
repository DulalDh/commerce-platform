<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreProductRequest;
use App\Modules\Catalog\Http\Requests\UpdateProductRequest;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketplace\Jobs\IndexProductJob;
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

        IndexProductJob::dispatch((string) $product->id);

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

        IndexProductJob::dispatch((string) $product->id);

        return ApiResponse::success(
            new ProductResource($product->load(['tags', 'variants', 'images']))
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        IndexProductJob::dispatch((string) $product->id);

        return ApiResponse::success(status: 204);
    }

    /**
     * Storefront listing - anonymous, tenant resolved from X-Tenant-ID via
     * the 'tenant.public' route middleware, and restricted to published
     * products only (unlike index(), which is the tenant-admin's own view
     * and includes drafts).
     */
    public function publicIndex(): JsonResponse
    {
        $products = Product::with(['tags', 'variants', 'images'])
            ->where('status', 'published')
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

    public function publicShow(string $slug): JsonResponse
    {
        $product = Product::with(['tags', 'variants', 'images'])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();

        return ApiResponse::success(new ProductResource($product));
    }
}
