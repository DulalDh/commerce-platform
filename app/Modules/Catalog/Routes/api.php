<?php

use App\Modules\Catalog\Http\Controllers\BrandController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\ProductImageController;
use App\Modules\Catalog\Http\Controllers\ProductVariantController;
use App\Modules\Catalog\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('products', ProductController::class)->except('store');
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('plan.limit:products')
        ->name('products.store');

    Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
    Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
    Route::patch('/products/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary']);

    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
});

// Anonymous storefront browsing. No auth:sanctum - tenant is resolved from
// the X-Tenant-ID header by ResolveTenant, trusted here only because
// 'tenant.public' marks these as read-only + published-only.
Route::middleware('tenant.public')->prefix('storefront')->group(function () {
    Route::get('/products', [ProductController::class, 'publicIndex']);
    Route::get('/products/{slug}', [ProductController::class, 'publicShow']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/tags', [TagController::class, 'index']);
});
