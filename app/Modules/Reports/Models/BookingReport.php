<?php

namespace App\Modules\Reports\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BookingReport extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'report_date', 'bookings_count', 'completed_count', 'cancelled_count', 'gross_revenue',
    ];

    protected $casts = [
        'report_date' => 'date',
        'gross_revenue' => 'decimal:2',
    ];
}
