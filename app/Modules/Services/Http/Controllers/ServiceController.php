<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Marketplace\Jobs\IndexServiceJob;
use App\Modules\Services\Http\Requests\StoreServiceRequest;
use App\Modules\Services\Http\Requests\UpdateServiceRequest;
use App\Modules\Services\Http\Resources\ServiceResource;
use App\Modules\Services\Models\Service;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(ServiceResource::collection(Service::orderByDesc('created_at')->paginate(20)));
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        IndexServiceJob::dispatch((string) $service->id);

        return ApiResponse::success(new ServiceResource($service), status: 201);
    }

    public function show(Service $service): JsonResponse
    {
        return ApiResponse::success(new ServiceResource($service));
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        IndexServiceJob::dispatch((string) $service->id);

        return ApiResponse::success(new ServiceResource($service));
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        IndexServiceJob::dispatch((string) $service->id);

        return ApiResponse::success(status: 204);
    }
}
