<?php

namespace App\Modules\Orders\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use BelongsToTenant, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'order_id', 'from_status', 'to_status', 'note', 'changed_by'];
}
