<?php

use App\Modules\Bookings\Http\Controllers\BookingController;
use App\Modules\Bookings\Http\Controllers\CalendarController;
use App\Modules\Bookings\Http\Controllers\HolidayController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/accept', [BookingController::class, 'accept']);
    Route::put('/bookings/{booking}/reject', [BookingController::class, 'reject']);
    Route::put('/bookings/{booking}/assign', [BookingController::class, 'assign']);
    Route::put('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

    Route::get('/providers/{tenant}/calendar', [CalendarController::class, 'show']);

    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy']);
});
