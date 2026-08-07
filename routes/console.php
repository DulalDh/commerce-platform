<?php

use App\Modules\Promotions\Jobs\ActivateFlashSalesJob;
use App\Modules\Reports\Jobs\GenerateDailyReportsJob;
use App\Modules\Reports\Jobs\RefreshDashboardStatsJob;
use App\Modules\Subscriptions\Jobs\GenerateSubscriptionInvoicesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RefreshDashboardStatsJob)->hourly();
Schedule::job(new GenerateSubscriptionInvoicesJob)->daily();
Schedule::job(new ActivateFlashSalesJob)->everyFiveMinutes();
Schedule::job(new GenerateDailyReportsJob)->dailyAt('01:00');
