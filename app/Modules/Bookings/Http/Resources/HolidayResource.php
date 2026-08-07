<?php

namespace App\Modules\Bookings\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class HolidayResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'name' => $this->name,
        ];
    }
}
