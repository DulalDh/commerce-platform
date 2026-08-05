<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class TagResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
        ];
    }
}
