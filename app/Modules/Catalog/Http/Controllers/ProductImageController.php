<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\UploadProductImageRequest;
use App\Modules\Catalog\Http\Resources\ProductImageResource;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Services\ProductImageVariantService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductImageController
{
    public function __construct(private readonly ProductImageVariantService $variantService)
    {
    }

    public function store(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        $disk = config('filesystems.default');
        $files = $request->file('images');
        $primaryIndex = $request->has('primary_index') ? (int) $request->input('primary_index') : null;
        $hasExistingImages = $product->images()->exists();
        $nextPosition = $product->images()->count();

        $images = DB::transaction(function () use ($files, $product, $disk, $primaryIndex, $hasExistingImages, $nextPosition) {
            $created = [];

            foreach (array_values($files) as $index => $file) {
                $variants = $this->variantService->store(
                    $file,
                    "products/{$product->id}/images",
                    $disk,
                );

                $isPrimary = $primaryIndex !== null
                    ? $index === $primaryIndex
                    : (! $hasExistingImages && $index === 0);

                $created[] = $product->images()->create([
                    'path' => $variants['original'],
                    'variants' => $variants,
                    'position' => $nextPosition + $index,
                    'is_primary' => $isPrimary,
                ]);
            }

            if ($primaryIndex !== null) {
                $primaryId = $created[$primaryIndex]->id ?? null;
                if ($primaryId) {
                    $product->images()->where('id', '!=', $primaryId)->update(['is_primary' => false]);
                }
            }

            return $created;
        });

        return ApiResponse::success(ProductImageResource::collection($images), status: 201);
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
