<?php

namespace App\Modules\CMS\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'question', 'answer', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'position' => 0,
    ];
}
