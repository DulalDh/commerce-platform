<?php

namespace App\Modules\Reports\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class TopSellingProductReportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_date' => $this->report_date?->toDateString(),
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'units_sold' => $this->units_sold,
            'revenue' => $this->revenue,
            'rank' => $this->rank,
        ];
    }
}
