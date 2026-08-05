<?php

namespace App\Modules\Subscriptions\Jobs;

use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Subscriptions\Models\SubscriptionInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled daily. For every active subscription whose current billing
 * period has ended, generates the next invoice and rolls the period
 * forward a month. Does not attempt to collect payment - that's a
 * separate concern (out of Phase 2 scope), this just produces the invoice.
 */
class GenerateSubscriptionInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('current_period_end', '<=', now())
            ->with('plan')
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $periodStart = $subscription->current_period_end;
                    $periodEnd = $periodStart->copy()->addMonth();

                    SubscriptionInvoice::create([
                        'tenant_id' => $subscription->tenant_id,
                        'subscription_id' => $subscription->id,
                        'amount' => $subscription->plan->price,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'status' => 'pending',
                    ]);

                    $subscription->update(['current_period_end' => $periodEnd]);
                }
            });
    }
}
