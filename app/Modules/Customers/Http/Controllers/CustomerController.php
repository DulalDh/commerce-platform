<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Orders\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $customerIds = Order::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('user_id');

        $customers = User::whereIn('id', $customerIds)
            ->with('lifetimeValue', 'customerTags')
            ->paginate(20);

        return ApiResponse::success(CustomerResource::collection($customers));
    }

    public function show(Request $request, User $customer): JsonResponse
    {
        $customer->load(['lifetimeValue', 'customerTags']);
        $customer->setRelation('orders', Order::where('user_id', $customer->id)->latest()->get());

        return ApiResponse::success(new CustomerResource($customer));
    }
}
