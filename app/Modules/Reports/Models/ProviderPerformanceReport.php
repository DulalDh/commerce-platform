<?php

namespace App\Modules\Reports\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProviderPerformanceReport extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'report_date', 'avg_overall_rating', 'reviews_count',
        'completed_bookings_count', 'cancelled_bookings_count', 'cancellation_rate',
    ];

    protected $casts = [
        'report_date' => 'date',
        'avg_overall_rating' => 'decimal:2',
        'cancellation_rate' => 'decimal:2',
    ];
}
