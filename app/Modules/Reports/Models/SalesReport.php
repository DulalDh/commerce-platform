<?php

namespace App\Modules\Reports\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'report_date', 'orders_count', 'gross_revenue',
        'discount_total', 'tax_total', 'net_revenue',
    ];

    protected $casts = [
        'report_date' => 'date',
        'gross_revenue' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'net_revenue' => 'decimal:2',
    ];
}
