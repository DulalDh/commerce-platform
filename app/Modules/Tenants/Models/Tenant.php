<?php

namespace App\Modules\Tenants\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'status',
        'subscription_plan_id',
        'domain',
        'subdomain',
        'description',
        'contact_email',
        'contact_phone',
        'address',
        'logo_path',
        'banner_path',
        'theme_color',
        'kyc_status',
        'kyc_documents',
    ];

    protected $casts = [
        'address' => 'array',
        'kyc_documents' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
