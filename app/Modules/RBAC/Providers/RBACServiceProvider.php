<?php

namespace App\Modules\RBAC\Providers;

use App\Modules\RBAC\Models\Role;
use App\Modules\RBAC\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RBACServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Role::class => RolePolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
