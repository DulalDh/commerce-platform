<?php

namespace App\Modules\Orders\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CartResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'subtotal' => $this->whenLoaded('items', fn () => $this->items->sum(fn ($i) => $i->unit_price * $i->quantity)),
        ];
    }
}
