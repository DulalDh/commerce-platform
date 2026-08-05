<?php

namespace App\Modules\RBAC\Database\Seeders;

use App\Modules\RBAC\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        collect(['manage-roles', 'manage-permissions', 'manage-tenants'])
            ->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $superAdmin = Role::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web', 'tenant_id' => null]
        );

        $superAdmin->syncPermissions(['manage-roles', 'manage-permissions', 'manage-tenants']);
    }
}
