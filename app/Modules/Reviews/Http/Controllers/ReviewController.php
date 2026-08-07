<?php

namespace App\Modules\Reviews\Http\Controllers;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Reviews\Http\Requests\StoreReviewRequest;
use App\Modules\Reviews\Http\Resources\ReviewResource;
use App\Modules\Reviews\Models\Review;
use App\Modules\Reviews\Services\ReviewService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ReviewController
{
    public function __construct(protected ReviewService $reviewService) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(ReviewResource::collection(Review::orderByDesc('created_at')->get()));
    }

    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        try {
            $review = $this->reviewService->createForBooking(
                $booking,
                $request->user()->id,
                $request->only(['quality_rating', 'behavior_rating', 'time_rating', 'communication_rating']),
                $request->comment,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(new ReviewResource($review), status: 201);
    }
}
