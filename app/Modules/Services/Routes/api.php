<?php

use App\Modules\Services\Http\Controllers\ProviderProfileController;
use App\Modules\Services\Http\Controllers\ServiceCategoryController;
use App\Modules\Services\Http\Controllers\ServiceController;
use App\Modules\Services\Http\Controllers\StaffController;
use App\Modules\Services\Http\Controllers\StaffLeaveController;
use App\Modules\Services\Http\Controllers\StaffScheduleController;
use App\Modules\Services\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/provider/profile', [ProviderProfileController::class, 'show']);
    Route::put('/provider/profile', [ProviderProfileController::class, 'update']);
    Route::post('/provider/certificates', [ProviderProfileController::class, 'uploadCertificate']);

    Route::apiResource('service-categories', ServiceCategoryController::class);
    Route::apiResource('services', ServiceController::class);

    Route::apiResource('staff', StaffController::class);
    Route::get('/staff/{staff}/leaves', [StaffLeaveController::class, 'index']);
    Route::post('/staff/{staff}/leaves', [StaffLeaveController::class, 'store']);
    Route::delete('/staff/{staff}/leaves/{leave}', [StaffLeaveController::class, 'destroy']);
    Route::get('/staff/{staff}/schedule', [StaffScheduleController::class, 'show']);

    Route::apiResource('vehicles', VehicleController::class)->except('show');
});
