<?php

namespace App\Modules\Tenants\Providers;

use App\Modules\Tenants\Support\RlsSchemaMacro;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TenantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        RlsSchemaMacro::register();

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
