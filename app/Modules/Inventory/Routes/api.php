<?php

use App\Modules\Inventory\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('inventory')->group(function () {
    Route::post('/stock-in', [InventoryController::class, 'stockIn']);
    Route::post('/stock-out', [InventoryController::class, 'stockOut']);
    Route::post('/adjustment', [InventoryController::class, 'adjustment']);
    Route::post('/transfer', [InventoryController::class, 'transfer']);
});
