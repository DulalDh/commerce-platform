<?php

namespace App\Modules\Bookings\Http\Controllers;

use App\Modules\Bookings\Http\Resources\BookingResource;
use App\Modules\Bookings\Models\Booking;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController
{
    protected const VIEWS = ['day' => 1, 'week' => 7, 'month' => 30];

    /**
     * GET /providers/{tenant}/calendar?view=month|week|day&date=YYYY-MM-DD
     * Restricted to that provider's own staff (or a Super Admin, who
     * bypasses tenant scoping entirely) - not a public availability feed.
     */
    public function show(Request $request, string $tenant): JsonResponse
    {
        $user = $request->user();

        if ($user->tenant_id !== null && $user->tenant_id !== $tenant) {
            return ApiResponse::error('You cannot view another provider\'s calendar.', [], 403);
        }

        $view = $request->input('view', 'week');
        $days = self::VIEWS[$view] ?? self::VIEWS['week'];

        $start = $request->filled('date') ? Carbon::parse($request->date) : now();
        $start = $view === 'month' ? $start->copy()->startOfMonth() : $start->copy()->startOfDay();
        $end = $start->copy()->addDays($days);

        $bookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenant)
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->with('service', 'staff')
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_slot_start')
            ->get();

        return ApiResponse::success(BookingResource::collection($bookings), [
            'view' => $view,
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ]);
    }
}
