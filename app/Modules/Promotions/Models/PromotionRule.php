<?php

namespace App\Modules\Promotions\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'name', 'type', 'config', 'is_active'];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];
}
