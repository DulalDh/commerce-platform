<?php

use App\Modules\Tenants\Http\Controllers\StoreProfileController;
use App\Modules\Tenants\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::post('/tenants', [TenantController::class, 'store']);

Route::middleware('auth:sanctum')->prefix('store')->group(function () {
    Route::get('/status', [StoreProfileController::class, 'status']);
    Route::put('/profile', [StoreProfileController::class, 'updateProfile']);
    Route::put('/theme-basic', [StoreProfileController::class, 'updateTheme']);
    Route::post('/kyc-documents', [StoreProfileController::class, 'uploadKycDocument']);
});
