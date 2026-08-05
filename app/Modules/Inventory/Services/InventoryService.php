<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Jobs\LowStockAlertJob;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(protected TenantContext $tenantContext) {}

    public function findOrCreateItem(string $productId, ?string $variantId = null): InventoryItem
    {
        return InventoryItem::firstOrCreate([
            'tenant_id' => $this->tenantContext->id(),
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);
    }

    public function stockIn(InventoryItem $item, int $quantity, ?string $reference = null, ?string $notes = null, ?int $userId = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('stockIn quantity must be positive.');
        }

        return $this->applyMovement($item, 'stock_in', $quantity, $reference, $notes, $userId);
    }

    public function stockOut(InventoryItem $item, int $quantity, ?string $reference = null, ?string $notes = null, ?int $userId = null): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('stockOut quantity must be positive.');
        }

        return $this->applyMovement($item, 'stock_out', -$quantity, $reference, $notes, $userId);
    }

    /**
     * $delta may be positive or negative - adjustment corrects the count to
     * whatever reality is (stock count, damage, etc), unlike stock_in/out
     * which represent actual physical movement.
     */
    public function adjust(InventoryItem $item, int $delta, ?string $notes = null, ?int $userId = null): StockMovement
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('adjustment delta must be non-zero.');
        }

        return $this->applyMovement($item, 'adjustment', $delta, null, $notes, $userId);
    }

    public function transfer(InventoryItem $from, InventoryItem $to, int $quantity, ?string $notes = null, ?int $userId = null): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('transfer quantity must be positive.');
        }

        return DB::transaction(function () use ($from, $to, $quantity, $notes, $userId) {
            $out = $this->applyMovement($from, 'transfer_out', -$quantity, null, $notes, $userId);
            $in = $this->applyMovement($to, 'transfer_in', $quantity, null, $notes, $userId);

            return ['from' => $out, 'to' => $in];
        });
    }

    /**
     * Locks the inventory row for the duration of the transaction so
     * concurrent movements on the same item serialize instead of racing on
     * a read-modify-write of quantity_on_hand.
     */
    protected function applyMovement(InventoryItem $item, string $type, int $signedQuantity, ?string $reference, ?string $notes, ?int $userId): StockMovement
    {
        return DB::transaction(function () use ($item, $type, $signedQuantity, $reference, $notes, $userId) {
            $locked = InventoryItem::withoutGlobalScopes()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newQuantity = $locked->quantity_on_hand + $signedQuantity;

            if ($newQuantity < 0) {
                throw new InvalidArgumentException('Insufficient stock for this movement.');
            }

            $locked->quantity_on_hand = $newQuantity;
            $locked->save();

            $movement = StockMovement::create([
                'tenant_id' => $locked->tenant_id,
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $signedQuantity,
                'quantity_after' => $newQuantity,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            if ($locked->isLowStock()) {
                LowStockAlertJob::dispatch((string) $locked->id);
            }

            return $movement;
        });
    }
}
