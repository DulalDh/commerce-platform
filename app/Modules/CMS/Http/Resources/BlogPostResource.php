<?php

namespace App\Modules\CMS\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class BlogPostResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->published_at,
        ];
    }
}
