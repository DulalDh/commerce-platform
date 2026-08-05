<?php

namespace App\Modules\Orders\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CartItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->unit_price * $this->quantity,
        ];
    }
}
