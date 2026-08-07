<?php

namespace App\Modules\Reviews\Models;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'booking_id', 'customer_id',
        'quality_rating', 'behavior_rating', 'time_rating', 'communication_rating',
        'overall_rating', 'comment',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
