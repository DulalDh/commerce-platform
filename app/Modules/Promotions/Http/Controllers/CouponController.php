<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Modules\Promotions\Http\Requests\StoreCouponRequest;
use App\Modules\Promotions\Http\Requests\UpdateCouponRequest;
use App\Modules\Promotions\Http\Resources\CouponResource;
use App\Modules\Promotions\Models\Coupon;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CouponController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(CouponResource::collection(Coupon::orderByDesc('created_at')->get()));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create($request->validated());

        return ApiResponse::success(new CouponResource($coupon), status: 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return ApiResponse::success(new CouponResource($coupon));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->validated());

        return ApiResponse::success(new CouponResource($coupon));
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();

        return ApiResponse::success(status: 204);
    }
}
