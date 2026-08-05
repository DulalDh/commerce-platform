<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a create action once the tenant's current plan limit for that
 * resource is reached. Usage: ->middleware('plan.limit:products') on a
 * route. Add a new resource by adding an entry to RESOURCE_COUNTERS - the
 * middleware itself never needs to change.
 */
class EnforcePlanLimit
{
    /** @var array<string, array{class-string<self>, string}> */
    protected const RESOURCE_COUNTERS = [
        'products' => [self::class, 'countProducts'],
        'staff' => [self::class, 'countStaff'],
    ];

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();

        if (! $user || $user->tenant_id === null) {
            return $next($request);
        }

        $tenant = Tenant::withoutGlobalScopes()->find($user->tenant_id);
        $limit = $tenant?->subscriptionPlan?->limits[$resource] ?? null;

        // No plan, or plan has no configured limit for this resource -
        // nothing to enforce.
        if ($limit === null) {
            return $next($request);
        }

        $counter = self::RESOURCE_COUNTERS[$resource] ?? null;
        $currentCount = $counter ? call_user_func($counter, $user->tenant_id) : 0;

        if ($currentCount >= $limit) {
            return response()->json([
                'message' => "Plan limit reached for {$resource} ({$limit}). Upgrade your subscription to add more.",
            ], 403);
        }

        return $next($request);
    }

    protected static function countProducts(string $tenantId): int
    {
        return Product::withoutGlobalScopes()->where('tenant_id', $tenantId)->count();
    }

    protected static function countStaff(string $tenantId): int
    {
        return User::where('tenant_id', $tenantId)->count();
    }
}
