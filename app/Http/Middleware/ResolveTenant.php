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

        // TenantContext is a scoped() binding, which only Octane actually
        // flushes between requests - on a plain worker/test process it can
        // behave like a singleton, so relying on "fresh instance per
        // request" is not safe. Reset explicitly every time rather than
        // trusting container lifecycle semantics for tenant isolation.
        $context->clear();

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
        // Idle state (no active tenant-scoped request on this connection)
        // defaults to bypass, matching queue workers and console commands -
        // '' would restrict any code that runs on this connection between
        // requests (e.g. test assertions right after an HTTP call, in the
        // same process/connection) to tenant_id IS NULL rows only. The next
        // real request always re-resolves and overwrites this before
        // running any query of its own, so relaxing the idle default here
        // never weakens isolation for an actual request.
        DB::statement("select set_config('app.current_tenant_id', 'bypass', false)");
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        if ($request->user() && $request->user()->tenant_id) {
            return Tenant::withoutGlobalScopes()->find($request->user()->tenant_id);
        }

        // The X-Tenant-ID header lets a Super Admin act on behalf of a
        // specific tenant (support/console use). It must never be honored
        // for an arbitrary authenticated caller - tenant_id === null also
        // describes every ordinary self-registered customer, not just admins.
        if ($this->isSuperAdmin($request) && ($tenantId = $request->header('X-Tenant-ID'))) {
            return Tenant::withoutGlobalScopes()->find($tenantId);
        }

        // Anonymous storefront browsing: there's no session at all, so
        // there's no user.tenant_id to fall back on. Only trust the header
        // here on routes explicitly marked 'tenant.public' (see
        // AllowPublicTenantHeader) - those are read-only and already scoped
        // to publicly-visible data, so letting an anonymous visitor pick
        // which tenant's storefront they're browsing isn't a privilege
        // escalation the way it would be on an authenticated/admin route.
        if ($this->allowsPublicTenantHeader($request) && ($tenantId = $request->header('X-Tenant-ID'))) {
            return Tenant::withoutGlobalScopes()->find($tenantId);
        }

        return null;
    }

    protected function isSuperAdmin(Request $request): bool
    {
        return (bool) $request->user()?->hasRole('super-admin');
    }

    protected function allowsPublicTenantHeader(Request $request): bool
    {
        $route = $request->route();

        return $route !== null && in_array('tenant.public', $route->gatherMiddleware(), true);
    }

    protected function setRlsSessionVar(TenantContext $context): void
    {
        $value = $context->isBypassing() ? 'bypass' : ($context->id() ?? '');

        DB::statement("select set_config('app.current_tenant_id', ?, false)", [$value]);
    }
}
