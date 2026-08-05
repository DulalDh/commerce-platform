<?php

namespace App\Http\Middleware;

use App\Models\AuditLog as AuditLogModel;
use App\Modules\Tenants\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLog
{
    protected const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! in_array($request->method(), self::MUTATING_METHODS, true)) {
            return;
        }

        if ($response->getStatusCode() >= 400) {
            return;
        }

        AuditLogModel::create([
            'tenant_id' => app(TenantContext::class)->id(),
            'user_id' => $request->user()?->id,
            'action' => $request->method().' '.$request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
