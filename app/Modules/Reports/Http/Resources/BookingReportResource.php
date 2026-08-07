<?php

namespace App\Modules\Reports\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class BookingReportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_date' => $this->report_date?->toDateString(),
            'bookings_count' => $this->bookings_count,
            'completed_count' => $this->completed_count,
            'cancelled_count' => $this->cancelled_count,
            'gross_revenue' => $this->gross_revenue,
        ];
    }
}
