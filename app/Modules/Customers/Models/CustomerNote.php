<?php

namespace App\Modules\Customers\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerNote extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'customer_id', 'note', 'created_by'];
}
