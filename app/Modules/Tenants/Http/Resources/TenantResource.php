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
            'created_at' => $this->created_at,
        ];
    }
}
