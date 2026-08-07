<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Services\Http\Requests\StoreVehicleRequest;
use App\Modules\Services\Http\Requests\UpdateVehicleRequest;
use App\Modules\Services\Http\Resources\VehicleResource;
use App\Modules\Services\Models\Vehicle;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VehicleController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(VehicleResource::collection(Vehicle::orderBy('type')->get()));
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return ApiResponse::success(new VehicleResource($vehicle), status: 201);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return ApiResponse::success(new VehicleResource($vehicle));
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return ApiResponse::success(status: 204);
    }
}
