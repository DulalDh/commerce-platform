<?php

namespace App\Modules\CMS\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class PageVersionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
