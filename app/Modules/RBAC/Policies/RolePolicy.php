<?php

namespace App\Modules\RBAC\Policies;

use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === null && $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->tenant_id === null && $user->hasRole('super-admin');
    }
}
