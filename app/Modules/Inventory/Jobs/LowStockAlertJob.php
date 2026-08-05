<?php

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Stub: logs the low-stock condition. Once the Notifications module exists
 * (Phase 4), this should dispatch a notification to the tenant's staff
 * instead of/in addition to logging.
 */
class LowStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $inventoryItemId) {}

    public function handle(): void
    {
        $item = InventoryItem::withoutGlobalScopes()->find($this->inventoryItemId);

        if (! $item || ! $item->isLowStock()) {
            return;
        }

        Log::warning('Low stock alert', [
            'tenant_id' => $item->tenant_id,
            'inventory_item_id' => $item->id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'quantity_on_hand' => $item->quantity_on_hand,
            'low_stock_threshold' => $item->low_stock_threshold,
        ]);
    }
}
