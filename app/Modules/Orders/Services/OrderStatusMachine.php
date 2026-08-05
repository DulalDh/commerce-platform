<?php

namespace App\Modules\Orders\Services;

use App\Modules\Customers\Jobs\RecalculateLifetimeValueJob;
use App\Modules\Orders\Jobs\GenerateInvoicePdfJob;
use App\Modules\Orders\Models\Order;
use InvalidArgumentException;

class OrderStatusMachine
{
    /** @var array<string, string[]> */
    protected const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['returned', 'refunded', 'exchanged'],
        'returned' => ['refunded', 'exchanged'],
        'cancelled' => [],
        'refunded' => [],
        'exchanged' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(Order $order, string $to, ?string $note = null, ?int $userId = null): Order
    {
        $from = $order->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException("Cannot transition order from [{$from}] to [{$to}].");
        }

        $order->status = $to;
        $order->save();

        $order->statusHistories()->create([
            'tenant_id' => $order->tenant_id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'changed_by' => $userId,
        ]);

        if ($to === 'confirmed') {
            GenerateInvoicePdfJob::dispatch((string) $order->id);
        }

        // Any status change can affect what counts toward LTV (confirmed
        // orders count, cancelled/refunded ones don't), so recompute on
        // every transition rather than only on terminal ones.
        RecalculateLifetimeValueJob::dispatch((string) $order->tenant_id, $order->user_id);

        return $order;
    }
}
