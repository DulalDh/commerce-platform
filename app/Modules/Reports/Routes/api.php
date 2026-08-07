<?php

use App\Modules\Reports\Http\Controllers\DashboardController;
use App\Modules\Reports\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/sales', [ReportController::class, 'sales']);
    Route::get('/bookings', [ReportController::class, 'bookings']);
    Route::get('/provider-performance', [ReportController::class, 'providerPerformance']);
    Route::get('/top-selling', [ReportController::class, 'topSelling']);
});
