<?php

use App\Modules\Customers\Http\Controllers\CustomerController;
use App\Modules\Customers\Http\Controllers\CustomerNoteController;
use App\Modules\Customers\Http\Controllers\CustomerTagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);

    Route::get('/customers/{customer}/notes', [CustomerNoteController::class, 'index']);
    Route::post('/customers/{customer}/notes', [CustomerNoteController::class, 'store']);

    Route::put('/customers/{customer}/tags', [CustomerTagController::class, 'sync']);
});
