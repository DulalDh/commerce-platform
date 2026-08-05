<?php

use App\Modules\Subscriptions\Http\Controllers\PlanController;
use App\Modules\Subscriptions\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Plans are platform-wide catalog data - anyone can browse them (e.g. a
// pricing page), only Super Admin can manage them.
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{plan}', [PlanController::class, 'show']);

Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::post('/plans', [PlanController::class, 'store']);
    Route::put('/plans/{plan}', [PlanController::class, 'update']);
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/current', [SubscriptionController::class, 'show']);
});
