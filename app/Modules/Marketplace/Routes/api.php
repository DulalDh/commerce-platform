<?php

use App\Modules\Marketplace\Http\Controllers\MarketplaceQueueController;
use App\Modules\Marketplace\Http\Controllers\ProductPublishController;
use App\Modules\Marketplace\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/search', [SearchController::class, 'index']);

Route::middleware('auth:sanctum')->put('/products/{product}/publish-to-marketplace', [ProductPublishController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin/marketplace')->group(function () {
    Route::get('/queue', [MarketplaceQueueController::class, 'index']);
    Route::put('/queue/{product}/approve', [MarketplaceQueueController::class, 'approve']);
    Route::put('/queue/{product}/reject', [MarketplaceQueueController::class, 'reject']);
});
