<?php

namespace App\Modules\Tenants\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class TenantResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'description' => $this->description,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'logo_path' => $this->logo_path,
            'banner_path' => $this->banner_path,
            'theme_color' => $this->theme_color,
            'kyc_status' => $this->kyc_status,
            'created_at' => $this->created_at,
        ];
    }
}
