<?php

namespace App\Modules\Tenants\Providers;

use App\Modules\Tenants\Support\RlsSchemaMacro;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
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

        $this->bootstrapQueueTenantBypass();
    }

    /**
     * Queue workers never go through ResolveTenant, so with RLS enabled on
     * tenant-owned tables every queued job would otherwise run with no
     * tenant context set - which RLS resolves to "tenant_id IS NULL only",
     * silently breaking any job that touches real tenant data. Jobs are
     * trusted internal code (like console commands, per TenantContext's own
     * bypass() docblock) and are individually responsible for scoping their
     * own queries correctly, so bypass tenant scoping for the duration of
     * each job.
     *
     * With the `sync` queue driver a job runs inline in the middle of the
     * current HTTP request (e.g. a controller dispatches a job that runs
     * immediately, then the controller keeps querying afterward) - so
     * "after" can't just clear the session var, it must restore exactly
     * what was in effect before this job started, or it would clobber the
     * surrounding request's own tenant context.
     */
    protected function bootstrapQueueTenantBypass(): void
    {
        $stack = [];

        Queue::before(function (JobProcessing $event) use (&$stack) {
            $context = app(TenantContext::class);
            $stack[] = [
                'bypass' => $context->isBypassing(),
                'tenant' => $context->tenant(),
            ];

            $context->bypass();
            DB::statement("select set_config('app.current_tenant_id', 'bypass', false)");
        });

        Queue::after(function (JobProcessed $event) use (&$stack) {
            $previous = array_pop($stack) ?? ['bypass' => false, 'tenant' => null];

            $context = app(TenantContext::class);
            $sessionValue = '';

            if ($previous['bypass']) {
                $context->bypass();
                $sessionValue = 'bypass';
            } elseif ($previous['tenant']) {
                $context->set($previous['tenant']);
                $sessionValue = $previous['tenant']->id;
            } else {
                $context->clear();
            }

            DB::statement("select set_config('app.current_tenant_id', ?, false)", [$sessionValue]);
        });
    }
}
