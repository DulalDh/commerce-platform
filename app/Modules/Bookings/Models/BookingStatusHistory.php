<?php

namespace App\Modules\Bookings\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BookingStatusHistory extends Model
{
    use BelongsToTenant, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'booking_id', 'from_status', 'to_status', 'note', 'changed_by'];
}
