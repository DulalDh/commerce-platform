<?php

namespace App\Modules\Customers\Http\Resources;

use App\Http\Resources\BaseResource;
use App\Modules\Catalog\Http\Resources\TagResource;
use App\Modules\Orders\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class CustomerResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'lifetime_value' => [
                'total_spent' => $this->lifetimeValue?->total_spent ?? '0.00',
                'order_count' => $this->lifetimeValue?->order_count ?? 0,
                'last_order_at' => $this->lifetimeValue?->last_order_at,
            ],
            'tags' => TagResource::collection($this->whenLoaded('customerTags')),
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
        ];
    }
}
