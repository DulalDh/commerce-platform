<?php

namespace App\Modules\Reports\Jobs;

use App\Modules\Orders\Models\Order;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshDashboardStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const CACHE_KEY = 'admin.dashboard';

    public function handle(): void
    {
        $ordersQuery = Order::withoutGlobalScopes()->whereNotIn('status', ['cancelled', 'refunded']);

        $stats = [
            'tenants' => [
                'total' => Tenant::withoutGlobalScopes()->count(),
                'active' => Tenant::withoutGlobalScopes()->where('status', 'active')->count(),
                'pending' => Tenant::withoutGlobalScopes()->where('status', 'pending')->count(),
                'suspended' => Tenant::withoutGlobalScopes()->where('status', 'suspended')->count(),
            ],
            'orders' => [
                'total' => (clone $ordersQuery)->count(),
                'total_revenue' => (clone $ordersQuery)->sum('total'),
            ],
            'computed_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY, $stats, now()->addDay());
    }
}
