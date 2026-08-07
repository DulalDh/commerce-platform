<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Services\Http\Requests\StoreStaffLeaveRequest;
use App\Modules\Services\Http\Resources\StaffLeaveResource;
use App\Modules\Services\Models\Staff;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaffLeaveController
{
    public function index(Staff $staff): JsonResponse
    {
        return ApiResponse::success(StaffLeaveResource::collection($staff->leaves()->orderByDesc('starts_on')->get()));
    }

    public function store(StoreStaffLeaveRequest $request, Staff $staff): JsonResponse
    {
        $leave = $staff->leaves()->create([
            'tenant_id' => $staff->tenant_id,
            'starts_on' => $request->starts_on,
            'ends_on' => $request->ends_on,
            'reason' => $request->reason,
        ]);

        return ApiResponse::success(new StaffLeaveResource($leave), status: 201);
    }

    public function destroy(Staff $staff, string $leave): JsonResponse
    {
        $staff->leaves()->where('id', $leave)->firstOrFail()->delete();

        return ApiResponse::success(status: 204);
    }
}
