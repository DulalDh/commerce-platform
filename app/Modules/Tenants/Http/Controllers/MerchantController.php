<?php

namespace App\Modules\Tenants\Http\Controllers;

use App\Modules\Tenants\Http\Resources\TenantResource;
use App\Modules\Tenants\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super Admin merchant management. Distinct from StoreProfileController,
 * which lets a tenant manage its own profile - these endpoints operate
 * across every tenant and require bypassing the tenant scope explicitly.
 */
class MerchantController
{
    public function index(Request $request): JsonResponse
    {
        $merchants = Tenant::withoutGlobalScopes()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(TenantResource::collection($merchants));
    }

    public function approve(Tenant $merchant): JsonResponse
    {
        $merchant->update(['status' => 'active']);

        return ApiResponse::success(new TenantResource($merchant));
    }

    public function suspend(Tenant $merchant): JsonResponse
    {
        $merchant->update(['status' => 'suspended']);

        return ApiResponse::success(new TenantResource($merchant));
    }
}
