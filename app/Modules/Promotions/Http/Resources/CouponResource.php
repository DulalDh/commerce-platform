<?php

namespace App\Modules\Promotions\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CouponResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount,
            'max_discount' => $this->max_discount,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'usage_limit' => $this->usage_limit,
            'usage_count' => $this->usage_count,
            'is_active' => $this->is_active,
        ];
    }
}
