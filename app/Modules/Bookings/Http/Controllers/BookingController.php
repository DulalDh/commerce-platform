<?php

namespace App\Modules\Bookings\Http\Controllers;

use App\Modules\Bookings\Http\Requests\AssignBookingRequest;
use App\Modules\Bookings\Http\Requests\RejectBookingRequest;
use App\Modules\Bookings\Http\Requests\StoreBookingRequest;
use App\Modules\Bookings\Http\Requests\UpdateBookingStatusRequest;
use App\Modules\Bookings\Http\Resources\BookingResource;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Services\BookingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BookingController
{
    public function __construct(protected BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        // Provider staff see emergency bookings surfaced first (priority
        // queue) - a customer's own booking list has no such notion, it's
        // just their history, so only apply the emergency-first ordering
        // for the provider side.
        $isProvider = $request->user()->tenant_id !== null;

        $bookings = Booking::with('service')
            ->when(! $isProvider, fn ($q) => $q->where('customer_id', $request->user()->id))
            ->when($isProvider, fn ($q) => $q->orderByDesc('is_emergency'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::success(BookingResource::collection($bookings));
    }

    public function show(Booking $booking): JsonResponse
    {
        return ApiResponse::success(new BookingResource($booking->load('service', 'staff')));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->create(
                $request->user()->id,
                $request->service_id,
                $request->scheduled_date,
                $request->scheduled_slot_start,
                $request->scheduled_slot_end,
                $request->address,
                $request->boolean('is_emergency'),
                $request->notes,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 409);
        }

        return ApiResponse::success(new BookingResource($booking), status: 201);
    }

    public function accept(Request $request, Booking $booking): JsonResponse
    {
        return $this->guarded(fn () => new BookingResource($this->bookingService->accept($booking, $request->user()->id)));
    }

    public function reject(RejectBookingRequest $request, Booking $booking): JsonResponse
    {
        return $this->guarded(fn () => new BookingResource(
            $this->bookingService->reject($booking, $request->reason, $request->user()->id)
        ));
    }

    public function assign(AssignBookingRequest $request, Booking $booking): JsonResponse
    {
        return $this->guarded(fn () => new BookingResource(
            $this->bookingService->assign($booking, $request->staff_id, $request->user()->id)
        ));
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        return $this->guarded(fn () => new BookingResource(
            $this->bookingService->transition($booking, $request->status, $request->note, $request->user()->id)
        ));
    }

    protected function guarded(callable $callback): JsonResponse
    {
        try {
            return ApiResponse::success($callback());
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }
    }
}
