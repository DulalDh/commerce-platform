<?php

namespace App\Modules\RBAC\Http\Controllers;

use App\Modules\RBAC\Http\Requests\StoreRoleRequest;
use App\Modules\RBAC\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RoleController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(Role::all());
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->input('guard_name', 'web'),
        ]);

        return ApiResponse::success($role, status: 201);
    }
}
