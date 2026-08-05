<?php

namespace App\Modules\Promotions\Jobs;

use App\Modules\Promotions\Models\FlashSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled every few minutes. Flips flash sales between scheduled/active/
 * ended based on their start/end timestamps - CartService reads the
 * `status` column rather than comparing timestamps itself, so "is this
 * discount live" is decided in exactly one place.
 */
class ActivateFlashSalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        FlashSale::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->update(['status' => 'active']);

        FlashSale::withoutGlobalScopes()
            ->whereIn('status', ['scheduled', 'active'])
            ->where('ends_at', '<=', now())
            ->update(['status' => 'ended']);
    }
}
