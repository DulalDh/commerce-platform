<?php

namespace App\Modules\CMS\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'author_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];
}
