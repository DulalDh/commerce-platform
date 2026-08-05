<?php

namespace App\Modules\Tenants\Scopes;

use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isBypassing()) {
            return;
        }

        if ($tenantId = $context->id()) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
