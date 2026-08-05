<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\UploadProductImageRequest;
use App\Modules\Catalog\Http\Resources\ProductImageResource;
use App\Modules\Catalog\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductImageController
{
    public function store(UploadProductImageRequest $request, Product $product): JsonResponse
    {
        $path = $request->file('image')->store("products/{$product->id}/images", 's3');

        $image = $product->images()->create([
            'path' => $path,
            'position' => $request->input('position', $product->images()->count()),
        ]);

        return ApiResponse::success(new ProductImageResource($image), status: 201);
    }

    public function destroy(Product $product, string $image): JsonResponse
    {
        $product->images()->where('id', $image)->firstOrFail()->delete();

        return ApiResponse::success(status: 204);
    }
}
