<?php

namespace App\Modules\Bookings\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'date', 'name'];

    protected $casts = [
        'date' => 'date',
    ];
}
