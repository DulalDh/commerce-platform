<?php

namespace App\Modules\Reports\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class SalesReportResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'report_date' => $this->report_date?->toDateString(),
            'orders_count' => $this->orders_count,
            'gross_revenue' => $this->gross_revenue,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'net_revenue' => $this->net_revenue,
        ];
    }
}
