<?php

namespace App\Modules\Services\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'service_category_id', 'name', 'slug', 'description',
        'pricing_model', 'price', 'duration_minutes', 'service_area',
        'required_equipment', 'cancellation_policy', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'service_area' => 'array',
        'required_equipment' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
