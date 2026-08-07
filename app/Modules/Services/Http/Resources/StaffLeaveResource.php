<?php

namespace App\Modules\Services\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class StaffLeaveResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'reason' => $this->reason,
        ];
    }
}
