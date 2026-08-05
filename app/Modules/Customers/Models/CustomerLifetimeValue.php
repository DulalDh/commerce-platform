<?php

namespace App\Modules\Customers\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerLifetimeValue extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'customer_id', 'total_spent', 'order_count', 'last_order_at', 'computed_at',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
        'computed_at' => 'datetime',
    ];
}
