<?php

namespace App\Modules\Promotions\Http\Resources;

use App\Http\Resources\BaseResource;
use App\Modules\Catalog\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class FlashSaleResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'discount_percentage' => $this->discount_percentage,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
