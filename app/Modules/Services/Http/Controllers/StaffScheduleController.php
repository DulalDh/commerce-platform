<?php

namespace App\Modules\Services\Http\Controllers;

use App\Modules\Bookings\Http\Resources\BookingResource;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Services\Http\Resources\StaffLeaveResource;
use App\Modules\Services\Models\Staff;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffScheduleController
{
    public function show(Request $request, Staff $staff): JsonResponse
    {
        $start = $request->filled('date') ? Carbon::parse($request->date) : now();
        $start = $start->copy()->startOfWeek();
        $end = $start->copy()->addDays(7);

        $bookings = Booking::where('staff_id', $staff->id)
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_slot_start')
            ->get();

        $leaves = $staff->leaves()
            ->where('ends_on', '>=', $start->toDateString())
            ->where('starts_on', '<=', $end->toDateString())
            ->get();

        return ApiResponse::success([
            'bookings' => BookingResource::collection($bookings),
            'leaves' => StaffLeaveResource::collection($leaves),
        ], ['range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()]]);
    }
}
