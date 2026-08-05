<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Http\Requests\StoreBrandRequest;
use App\Modules\Catalog\Http\Requests\UpdateBrandRequest;
use App\Modules\Catalog\Http\Resources\BrandResource;
use App\Modules\Catalog\Models\Brand;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BrandController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(BrandResource::collection(Brand::orderBy('name')->get()));
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::create($request->validated());

        return ApiResponse::success(new BrandResource($brand), status: 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return ApiResponse::success(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $brand->update($request->validated());

        return ApiResponse::success(new BrandResource($brand));
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return ApiResponse::success(status: 204);
    }
}
