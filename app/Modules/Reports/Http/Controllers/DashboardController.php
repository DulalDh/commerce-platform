<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Reports\Jobs\RefreshDashboardStatsJob;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController
{
    public function show(): JsonResponse
    {
        $stats = Cache::get(RefreshDashboardStatsJob::CACHE_KEY);

        if ($stats === null) {
            // Nothing cached yet (fresh install, or the scheduled job hasn't
            // run) - compute synchronously once rather than showing nothing.
            (new RefreshDashboardStatsJob)->handle();
            $stats = Cache::get(RefreshDashboardStatsJob::CACHE_KEY);
        }

        return ApiResponse::success($stats);
    }
}
