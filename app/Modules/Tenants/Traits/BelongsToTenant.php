<?php

namespace App\Modules\Tenants\Traits;

use App\Modules\Tenants\Scopes\TenantScope;
use App\Modules\Tenants\Support\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $model->tenant_id ??= app(TenantContext::class)->id();
        });
    }
}
