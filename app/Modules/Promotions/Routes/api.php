<?php

use App\Modules\Promotions\Http\Controllers\CouponController;
use App\Modules\Promotions\Http\Controllers\FlashSaleController;
use App\Modules\Promotions\Http\Controllers\PromotionRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('coupons', CouponController::class);
    Route::apiResource('flash-sales', FlashSaleController::class)->except('update');
    Route::apiResource('promotion-rules', PromotionRuleController::class)->only(['index', 'store', 'destroy']);
});
