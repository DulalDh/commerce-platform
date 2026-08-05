<?php

namespace App\Modules\RBAC\Models;

use App\Modules\Tenants\Traits\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extends Spatie's Role model to add tenant scoping via the same
 * BelongsToTenant trait used by other tenant-owned models — roles are
 * tenant-scoped except Super Admin's global roles (tenant_id null).
 */
class Role extends SpatieRole
{
    use BelongsToTenant;

    protected $fillable = ['name', 'guard_name', 'tenant_id'];
}
