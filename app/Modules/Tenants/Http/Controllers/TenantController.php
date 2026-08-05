<?php

namespace App\Modules\Tenants\Http\Controllers;

use App\Models\User;
use App\Modules\RBAC\Models\Role;
use App\Modules\Tenants\Http\Requests\StoreTenantRequest;
use App\Modules\Tenants\Http\Resources\TenantResource;
use App\Modules\Tenants\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantController
{
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->name,
                'type' => $request->type,
                'status' => 'pending',
                'subdomain' => $request->subdomain,
                'domain' => $request->domain,
            ]);

            // Registration has no resolved tenant context yet (no auth user,
            // no X-Tenant-ID header) - the owner user and role we're about to
            // insert are RLS-protected rows, so the session var must be set
            // to the tenant we just created before those inserts run.
            DB::statement("select set_config('app.current_tenant_id', ?, false)", [$tenant->id]);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'password' => Hash::make($request->owner_password),
            ]);

            // No tenant context is resolved yet during registration itself, so
            // create the role explicitly scoped to the new tenant rather than
            // relying on BelongsToTenant's creating-hook default.
            $ownerRole = Role::withoutGlobalScopes()->firstOrCreate([
                'name' => 'tenant-owner',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $owner->assignRole($ownerRole);

            return $tenant;
        });

        return ApiResponse::success(new TenantResource($tenant), status: 201);
    }
}
