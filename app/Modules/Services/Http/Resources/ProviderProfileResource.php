<?php

namespace App\Modules\Services\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProviderProfileResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'years_experience' => $this->years_experience,
            'service_area' => $this->service_area,
            'certificates' => $this->certificates,
            'verification_status' => $this->verification_status,
        ];
    }
}
