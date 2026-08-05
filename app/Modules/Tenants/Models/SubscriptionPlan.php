<?php

namespace App\Modules\Tenants\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'slug', 'price', 'limits'];

    protected $casts = [
        'limits' => 'array',
        'price' => 'decimal:2',
    ];
}
