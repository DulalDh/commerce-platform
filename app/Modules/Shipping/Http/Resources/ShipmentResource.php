<?php

namespace App\Modules\Shipping\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ShipmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'provider' => $this->provider,
            'tracking_number' => $this->tracking_number,
            'status' => $this->status,
            'rate' => $this->rate,
            'currency' => $this->currency,
            'created_at' => $this->created_at,
        ];
    }
}
