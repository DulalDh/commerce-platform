<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Services\Http\Requests\StoreStaffRequest;
use App\Modules\Services\Http\Requests\UpdateStaffRequest;
use App\Modules\Services\Http\Resources\StaffResource;
use App\Modules\Services\Models\Staff;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaffController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(StaffResource::collection(Staff::orderBy('name')->get()));
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = Staff::create($request->validated());

        return ApiResponse::success(new StaffResource($staff), status: 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return ApiResponse::success(new StaffResource($staff));
    }

    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $staff->update($request->validated());

        return ApiResponse::success(new StaffResource($staff));
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return ApiResponse::success(status: 204);
    }
}
