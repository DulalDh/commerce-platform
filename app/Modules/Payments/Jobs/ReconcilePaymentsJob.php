<?php

namespace App\Modules\Payments\Jobs;

use App\Modules\Payments\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled job: flags payments stuck in "pending" past a reasonable window
 * (webhook likely lost or never sent) for manual/staff follow-up. Does not
 * mutate payment state itself - reconciliation against the gateway's own
 * transaction records is gateway-specific and out of Phase 1 scope.
 */
class ReconcilePaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $stale = Payment::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(2))
            ->get();

        foreach ($stale as $payment) {
            Log::warning('Payment stuck in pending - possible lost webhook', [
                'tenant_id' => $payment->tenant_id,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $payment->gateway,
                'created_at' => $payment->created_at,
            ]);
        }
    }
}
