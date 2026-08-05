<?php

namespace App\Modules\Subscriptions\Http\Controllers;

use App\Modules\Subscriptions\Http\Requests\SubscribeRequest;
use App\Modules\Subscriptions\Http\Resources\SubscriptionResource;
use App\Modules\Subscriptions\Models\Subscription;
use App\Modules\Tenants\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController
{
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $subscription = DB::transaction(function () use ($request) {
            // A tenant has at most one active subscription - switching plans
            // replaces it rather than stacking.
            Subscription::where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $subscription = Subscription::create([
                'tenant_id' => $request->user()->tenant_id,
                'subscription_plan_id' => $request->plan_id,
                'status' => 'active',
                'started_at' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            Tenant::withoutGlobalScopes()
                ->whereKey($request->user()->tenant_id)
                ->update(['subscription_plan_id' => $request->plan_id]);

            return $subscription;
        });

        return ApiResponse::success(new SubscriptionResource($subscription->load('plan')), status: 201);
    }

    public function show(Request $request): JsonResponse
    {
        $subscription = Subscription::where('status', 'active')->with('plan')->first();

        return ApiResponse::success($subscription ? new SubscriptionResource($subscription) : null);
    }
}
