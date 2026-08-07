<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Services\Http\Requests\StoreServiceCategoryRequest;
use App\Modules\Services\Http\Requests\UpdateServiceCategoryRequest;
use App\Modules\Services\Http\Resources\ServiceCategoryResource;
use App\Modules\Services\Models\ServiceCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceCategoryController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(ServiceCategoryResource::collection(ServiceCategory::orderBy('name')->get()));
    }

    public function store(StoreServiceCategoryRequest $request): JsonResponse
    {
        $category = ServiceCategory::create($request->validated());

        return ApiResponse::success(new ServiceCategoryResource($category), status: 201);
    }

    public function show(ServiceCategory $serviceCategory): JsonResponse
    {
        return ApiResponse::success(new ServiceCategoryResource($serviceCategory));
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $serviceCategory->update($request->validated());

        return ApiResponse::success(new ServiceCategoryResource($serviceCategory));
    }

    public function destroy(ServiceCategory $serviceCategory): JsonResponse
    {
        $serviceCategory->delete();

        return ApiResponse::success(status: 204);
    }
}
