<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Modules\Subscriptions\Http\Requests\StorePlanRequest;
use App\Modules\Subscriptions\Http\Requests\UpdatePlanRequest;
use App\Modules\Subscriptions\Http\Resources\PlanResource;
use App\Modules\Tenants\Models\SubscriptionPlan;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlanController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(PlanResource::collection(SubscriptionPlan::all()));
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return ApiResponse::success(new PlanResource($plan), status: 201);
    }

    public function show(SubscriptionPlan $plan): JsonResponse
    {
        return ApiResponse::success(new PlanResource($plan));
    }

    public function update(UpdatePlanRequest $request, SubscriptionPlan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return ApiResponse::success(new PlanResource($plan));
    }

    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        $plan->delete();

        return ApiResponse::success(status: 204);
    }
}
