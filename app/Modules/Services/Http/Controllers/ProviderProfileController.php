<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Services\Http\Requests\UpdateProviderProfileRequest;
use App\Modules\Services\Http\Requests\UploadCertificateRequest;
use App\Modules\Services\Http\Resources\ProviderProfileResource;
use App\Modules\Services\Models\ProviderProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProviderProfileController
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new ProviderProfileResource($this->profile($request)));
    }

    public function update(UpdateProviderProfileRequest $request): JsonResponse
    {
        $profile = $this->profile($request);
        $profile->update($request->only(['years_experience', 'service_area']));

        return ApiResponse::success(new ProviderProfileResource($profile));
    }

    public function uploadCertificate(UploadCertificateRequest $request): JsonResponse
    {
        $profile = $this->profile($request);

        $path = $request->file('document')->store("tenants/{$profile->tenant_id}/certificates", 's3');

        $certificates = $profile->certificates ?? [];
        $certificates[] = [
            'id' => (string) Str::uuid(),
            'name' => $request->name,
            'path' => $path,
            'uploaded_at' => now()->toIso8601String(),
        ];

        $profile->certificates = $certificates;
        $profile->verification_status = 'pending';
        $profile->save();

        return ApiResponse::success(new ProviderProfileResource($profile), status: 201);
    }

    protected function profile(Request $request): ProviderProfile
    {
        return ProviderProfile::firstOrCreate(['tenant_id' => $request->user()->tenant_id]);
    }
}
