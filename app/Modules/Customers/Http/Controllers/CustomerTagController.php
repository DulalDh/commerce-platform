<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Http\Requests\SyncCustomerTagsRequest;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CustomerTagController
{
    public function sync(SyncCustomerTagsRequest $request, User $customer): JsonResponse
    {
        $customer->customerTags()->sync($request->tag_ids);

        return ApiResponse::success(new CustomerResource($customer->load('customerTags')));
    }
}
