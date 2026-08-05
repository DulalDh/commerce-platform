<?php

namespace App\Modules\Subscriptions\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class PlanResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'limits' => $this->limits,
        ];
    }
}
