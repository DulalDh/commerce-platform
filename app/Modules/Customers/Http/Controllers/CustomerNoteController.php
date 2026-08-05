<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Models\User;
use App\Modules\Customers\Http\Requests\StoreCustomerNoteRequest;
use App\Modules\Customers\Http\Resources\CustomerNoteResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CustomerNoteController
{
    public function index(User $customer): JsonResponse
    {
        return ApiResponse::success(
            CustomerNoteResource::collection($customer->customerNotes()->latest()->get())
        );
    }

    public function store(StoreCustomerNoteRequest $request, User $customer): JsonResponse
    {
        $note = $customer->customerNotes()->create([
            'tenant_id' => $request->user()->tenant_id,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return ApiResponse::success(new CustomerNoteResource($note), status: 201);
    }
}
