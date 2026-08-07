<?php

namespace App\Modules\Bookings\Http\Controllers;

use App\Modules\Bookings\Http\Requests\StoreHolidayRequest;
use App\Modules\Bookings\Http\Resources\HolidayResource;
use App\Modules\Bookings\Models\Holiday;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HolidayController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(HolidayResource::collection(Holiday::orderBy('date')->get()));
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = Holiday::create($request->validated());

        return ApiResponse::success(new HolidayResource($holiday), status: 201);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();

        return ApiResponse::success(status: 204);
    }
}
