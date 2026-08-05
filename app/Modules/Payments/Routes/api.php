<?php

use App\Modules\Payments\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->post('/payments/initiate', [PaymentController::class, 'initiate']);

// Public: gateways call this directly, no user session involved.
Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook'])
    ->name('payments.webhook');
