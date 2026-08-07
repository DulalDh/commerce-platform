<?php

namespace App\Modules\Services\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class VehicleResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'type' => $this->type,
            'registration_number' => $this->registration_number,
            'capacity_kg' => $this->capacity_kg,
            'is_active' => $this->is_active,
        ];
    }
}
