<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Modules\Promotions\Http\Requests\StorePromotionRuleRequest;
use App\Modules\Promotions\Http\Resources\PromotionRuleResource;
use App\Modules\Promotions\Models\PromotionRule;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PromotionRuleController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(PromotionRuleResource::collection(PromotionRule::orderByDesc('created_at')->get()));
    }

    public function store(StorePromotionRuleRequest $request): JsonResponse
    {
        $rule = PromotionRule::create($request->only(['name', 'type', 'config', 'is_active']));

        return ApiResponse::success(new PromotionRuleResource($rule), status: 201);
    }

    public function destroy(PromotionRule $promotionRule): JsonResponse
    {
        $promotionRule->delete();

        return ApiResponse::success(status: 204);
    }
}
