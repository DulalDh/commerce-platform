<?php

namespace App\Modules\Tenants\Http\Controllers;

use App\Modules\Tenants\Http\Requests\UpdateStoreProfileRequest;
use App\Modules\Tenants\Http\Requests\UpdateStoreThemeRequest;
use App\Modules\Tenants\Http\Requests\UploadKycDocumentRequest;
use App\Modules\Tenants\Http\Resources\TenantResource;
use App\Modules\Tenants\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreProfileController
{
    public function status(Request $request): JsonResponse
    {
        return ApiResponse::success(new TenantResource($this->tenant($request)));
    }

    public function updateProfile(UpdateStoreProfileRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);
        $tenant->update($request->only([
            'name', 'description', 'contact_email', 'contact_phone', 'address',
        ]));

        return ApiResponse::success(new TenantResource($tenant));
    }

    public function updateTheme(UpdateStoreThemeRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        if ($request->filled('theme_color')) {
            $tenant->theme_color = $request->theme_color;
        }

        if ($request->hasFile('logo')) {
            $tenant->logo_path = $request->file('logo')->store("tenants/{$tenant->id}/branding", 's3');
        }

        if ($request->hasFile('banner')) {
            $tenant->banner_path = $request->file('banner')->store("tenants/{$tenant->id}/branding", 's3');
        }

        $tenant->save();

        return ApiResponse::success(new TenantResource($tenant));
    }

    public function uploadKycDocument(UploadKycDocumentRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $path = $request->file('document')->store("tenants/{$tenant->id}/kyc", 's3');

        $documents = $tenant->kyc_documents ?? [];
        $documents[] = [
            'id' => (string) Str::uuid(),
            'type' => $request->type,
            'path' => $path,
            'uploaded_at' => now()->toIso8601String(),
        ];

        $tenant->kyc_documents = $documents;
        $tenant->kyc_status = 'pending';
        $tenant->save();

        return ApiResponse::success(new TenantResource($tenant), status: 201);
    }

    protected function tenant(Request $request): Tenant
    {
        return Tenant::withoutGlobalScopes()->findOrFail($request->user()->tenant_id);
    }
}
