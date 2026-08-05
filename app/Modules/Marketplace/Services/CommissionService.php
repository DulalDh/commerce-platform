<?php

namespace App\Modules\Marketplace\Services;

use App\Modules\Marketplace\Models\Commission;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Called at order-completion time (order confirmed). Idempotent by the
     * unique constraint on commissions.order_id - if this is ever invoked
     * twice for the same order (retry, duplicate event), the second call
     * silently no-ops instead of double-charging commission.
     */
    public function calculateForOrder(Order $order): ?Commission
    {
        $rate = (float) config('marketplace.commission_rate');

        try {
            // Wrapped in its own transaction (a SAVEPOINT, since this always
            // runs inside an outer transaction from the order status
            // transition) - Postgres poisons the whole enclosing transaction
            // on a unique-violation even when the exception is caught, so
            // without this the caller's subsequent queries would fail with
            // "current transaction is aborted".
            return DB::transaction(fn () => Commission::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'order_total' => $order->total,
                'rate' => $rate,
                'amount' => round($order->total * $rate, 2),
                'status' => 'pending',
            ]));
        } catch (UniqueConstraintViolationException) {
            return Commission::withoutGlobalScopes()->where('order_id', $order->id)->first();
        }
    }
}
