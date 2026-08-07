<?php

namespace App\Modules\Reports\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ProviderPerformanceReportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_date' => $this->report_date?->toDateString(),
            'avg_overall_rating' => $this->avg_overall_rating,
            'reviews_count' => $this->reviews_count,
            'completed_bookings_count' => $this->completed_bookings_count,
            'cancelled_bookings_count' => $this->cancelled_bookings_count,
            'cancellation_rate' => $this->cancellation_rate,
        ];
    }
}
