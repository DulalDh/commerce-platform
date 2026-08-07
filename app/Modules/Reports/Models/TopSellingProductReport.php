<?php

namespace App\Modules\Reports\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TopSellingProductReport extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'report_date', 'product_id', 'product_name', 'units_sold', 'revenue', 'rank',
    ];

    protected $casts = [
        'report_date' => 'date',
        'revenue' => 'decimal:2',
    ];
}
