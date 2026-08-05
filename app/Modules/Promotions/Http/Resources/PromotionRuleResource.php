<?php

namespace App\Modules\Promotions\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class PromotionRuleResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'config' => $this->config,
            'is_active' => $this->is_active,
        ];
    }
}
