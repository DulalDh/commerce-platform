<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\UploadProductImageRequest;
use App\Modules\Catalog\Http\Resources\ProductImageResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Services\ProductImageVariantService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductImageController
{
    public function __construct(private readonly ProductImageVariantService $variantService)
    {
    }

    public function store(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        $disk = config('filesystems.default');
        $variants = $this->variantService->store(
            $request->file('image'),
            "products/{$product->id}/images",
            $disk,
        );

        $isFirstImage = ! $product->images()->exists();

        $image = $product->images()->create([
            'path' => $variants['original'],
            'variants' => $variants,
            'position' => $request->input('position', $product->images()->count()),
            'is_primary' => $isFirstImage,
        ]);

        return ApiResponse::success(new ProductImageResource($image), status: 201);
    }

    public function destroy(Product $product, string $image): JsonResponse
    {
        $disk = config('filesystems.default');

        /** @var ProductImage $productImage */
        $productImage = $product->images()->where('id', $image)->firstOrFail();
        $wasPrimary = $productImage->is_primary;

        $this->variantService->delete($productImage->variants, $productImage->path, $disk);
        $productImage->delete();

        if ($wasPrimary) {
            $next = $product->images()->orderBy('position')->first();
            $next?->update(['is_primary' => true]);
        }

        return ApiResponse::success(status: 204);
    }

    public function setPrimary(Product $product, string $image): JsonResponse
    {
        /** @var ProductImage $productImage */
        $productImage = $product->images()->where('id', $image)->firstOrFail();

        $product->images()->where('id', '!=', $productImage->id)->update(['is_primary' => false]);
        $productImage->update(['is_primary' => true]);

        return ApiResponse::success(new ProductImageResource($productImage));
    }
}
