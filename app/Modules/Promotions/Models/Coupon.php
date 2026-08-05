<?php

namespace App\Modules\Promotions\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'code', 'type', 'value', 'min_order_amount', 'max_discount',
        'starts_at', 'ends_at', 'usage_limit', 'usage_count', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'usage_count' => 0,
        'min_order_amount' => 0,
    ];
}
