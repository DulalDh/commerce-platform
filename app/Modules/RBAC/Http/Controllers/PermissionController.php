<?php

namespace App\Modules\RBAC\Http\Controllers;

use App\Modules\RBAC\Http\Requests\StorePermissionRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(Permission::all());
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->input('guard_name', 'web'),
        ]);

        return ApiResponse::success($permission, status: 201);
    }
}
