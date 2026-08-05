<?php

use App\Modules\Shipping\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::post('/shipping/rate', [ShippingController::class, 'calculateRate']);

Route::middleware('auth:sanctum')
    ->post('/orders/{order}/shipments', [ShippingController::class, 'createShipment']);

// Public: couriers call this directly, no user session involved.
Route::post('/shipping/webhook/{provider}', [ShippingController::class, 'webhook'])
    ->name('shipping.webhook');
