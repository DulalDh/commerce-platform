<?php

namespace App\Modules\Bookings\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class BookingResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'service_id' => $this->service_id,
            'staff_id' => $this->staff_id,
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'scheduled_slot_start' => $this->scheduled_slot_start,
            'scheduled_slot_end' => $this->scheduled_slot_end,
            'address' => $this->address,
            'is_emergency' => $this->is_emergency,
            'price' => $this->price,
            'emergency_charge' => $this->emergency_charge,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
        ];
    }
}
