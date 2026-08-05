<?php

namespace App\Modules\Inventory\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class StockMovementResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'quantity_after' => $this->quantity_after,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
