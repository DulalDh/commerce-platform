<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route marker only - carries no behavior of its own. Its presence on a
 * route's middleware stack is what ResolveTenant checks before trusting an
 * unauthenticated caller's X-Tenant-ID header. Only attach this to routes
 * that are read-only and already restricted to publicly-visible data (e.g.
 * published products), since it lets any anonymous caller pick a tenant.
 */
class AllowPublicTenantHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
