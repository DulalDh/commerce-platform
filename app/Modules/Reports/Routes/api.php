<?php

use App\Modules\Reports\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
});
