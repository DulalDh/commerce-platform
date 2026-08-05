<?php

namespace App\Modules\Customers\Jobs;

use App\Modules\Customers\Models\CustomerLifetimeValue;
use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateLifetimeValueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const EXCLUDED_STATUSES = ['cancelled', 'refunded'];

    public function __construct(public string $tenantId, public int $customerId) {}

    public function handle(): void
    {
        $orders = Order::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('user_id', $this->customerId)
            ->whereNotIn('status', self::EXCLUDED_STATUSES)
            ->get();

        CustomerLifetimeValue::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $this->tenantId, 'customer_id' => $this->customerId],
            [
                'total_spent' => $orders->sum('total'),
                'order_count' => $orders->count(),
                'last_order_at' => $orders->max('created_at'),
                'computed_at' => now(),
            ]
        );
    }
}
