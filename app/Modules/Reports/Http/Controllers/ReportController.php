<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Reports\Http\Resources\BookingReportResource;
use App\Modules\Reports\Http\Resources\ProviderPerformanceReportResource;
use App\Modules\Reports\Http\Resources\SalesReportResource;
use App\Modules\Reports\Http\Resources\TopSellingProductReportResource;
use App\Modules\Reports\Models\BookingReport;
use App\Modules\Reports\Models\ProviderPerformanceReport;
use App\Modules\Reports\Models\SalesReport;
use App\Modules\Reports\Models\TopSellingProductReport;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * All endpoints read exclusively from the pre-computed report tables
 * GenerateDailyReportsJob populates - never live-aggregating
 * Orders/Bookings/Reviews on request.
 */
class ReportController
{
    public function sales(Request $request): JsonResponse
    {
        return ApiResponse::success(SalesReportResource::collection($this->inRange(SalesReport::query(), $request)));
    }

    public function bookings(Request $request): JsonResponse
    {
        return ApiResponse::success(BookingReportResource::collection($this->inRange(BookingReport::query(), $request)));
    }

    public function providerPerformance(Request $request): JsonResponse
    {
        return ApiResponse::success(
            ProviderPerformanceReportResource::collection($this->inRange(ProviderPerformanceReport::query(), $request))
        );
    }

    public function topSelling(Request $request): JsonResponse
    {
        $reports = TopSellingProductReport::query()
            ->when($request->filled('from'), fn ($q) => $q->where('report_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->where('report_date', '<=', $request->to))
            ->orderByDesc('report_date')
            ->orderBy('rank')
            ->get();

        return ApiResponse::success(TopSellingProductReportResource::collection($reports));
    }

    protected function inRange($query, Request $request)
    {
        return $query
            ->when($request->filled('from'), fn ($q) => $q->where('report_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->where('report_date', '<=', $request->to))
            ->orderByDesc('report_date')
            ->get();
    }
}
