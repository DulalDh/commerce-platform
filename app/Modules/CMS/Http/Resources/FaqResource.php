<?php

namespace App\Modules\CMS\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class FaqResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'position' => $this->position,
            'is_active' => $this->is_active,
        ];
    }
}
