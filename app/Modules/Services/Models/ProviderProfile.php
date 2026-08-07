<?php

namespace App\Modules\Services\Models;

use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderProfile extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = [
        'tenant_id', 'years_experience', 'service_area', 'certificates', 'verification_status',
    ];

    protected $casts = [
        'service_area' => 'array',
        'certificates' => 'array',
    ];

    protected $attributes = [
        'verification_status' => 'not_submitted',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
