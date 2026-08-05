<?php

namespace App\Http\Middleware;

use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current request's tenant and sets it on both isolation layers:
 * the TenantContext singleton (read by the Eloquent global scope) and the
 * Postgres session variable app.current_tenant_id (read by RLS policies).
 *
 * The session var MUST be reset on terminate() - if this ever runs under a
 * persistent worker (Octane, queue workers reusing a connection), a stale
 * value would leak tenant context into the next request on the same connection.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            $context->set($tenant);
        } elseif ($this->isSuperAdmin($request)) {
            $context->bypass();
        }

        $this->setRlsSessionVar($context);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        DB::statement("select set_config('app.current_tenant_id', '', false)");
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        if ($request->user() && $request->user()->tenant_id) {
            return Tenant::withoutGlobalScopes()->find($request->user()->tenant_id);
        }

        if ($tenantId = $request->header('X-Tenant-ID')) {
            return Tenant::withoutGlobalScopes()->find($tenantId);
        }

        return null;
    }

    protected function isSuperAdmin(Request $request): bool
    {
        return $request->user() && $request->user()->tenant_id === null;
    }

    protected function setRlsSessionVar(TenantContext $context): void
    {
        $value = $context->isBypassing() ? 'bypass' : ($context->id() ?? '');

        DB::statement("select set_config('app.current_tenant_id', ?, false)", [$value]);
    }
}
