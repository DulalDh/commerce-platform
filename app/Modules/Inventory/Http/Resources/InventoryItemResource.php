<?php

namespace App\Modules\Inventory\Http\Resources;

use App\Http\Resources\BaseResource;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Http\Request;

/**
 * @property InventoryItem $resource
 */
class InventoryItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_low_stock' => $this->resource->isLowStock(),
        ];
    }
}
