<?php

namespace App\Modules\Bookings\Models;

use App\Models\User;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\Staff;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'customer_id', 'service_id', 'staff_id', 'status',
        'scheduled_date', 'scheduled_slot_start', 'scheduled_slot_end',
        'address', 'is_emergency', 'price', 'emergency_charge', 'notes', 'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'address' => 'array',
        'is_emergency' => 'boolean',
        'price' => 'decimal:2',
        'emergency_charge' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_emergency' => false,
        'emergency_charge' => 0,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return HasMany<BookingStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }
}
