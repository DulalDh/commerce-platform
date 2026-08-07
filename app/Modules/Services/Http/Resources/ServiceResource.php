<?php

namespace App\Modules\Services\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ServiceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_category_id' => $this->service_category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'pricing_model' => $this->pricing_model,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'service_area' => $this->service_area,
            'required_equipment' => $this->required_equipment,
            'cancellation_policy' => $this->cancellation_policy,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
