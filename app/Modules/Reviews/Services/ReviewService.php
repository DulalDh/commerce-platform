<?php

namespace App\Modules\Reviews\Services;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Reviews\Models\Review;
use InvalidArgumentException;

class ReviewService
{
    public function createForBooking(Booking $booking, int $customerId, array $ratings, ?string $comment): Review
    {
        if ($booking->customer_id !== $customerId) {
            throw new InvalidArgumentException('You can only review your own bookings.');
        }

        if ($booking->status !== 'completed') {
            throw new InvalidArgumentException('Only completed bookings can be reviewed.');
        }

        if (Review::withoutGlobalScopes()->where('booking_id', $booking->id)->exists()) {
            throw new InvalidArgumentException('This booking has already been reviewed.');
        }

        $overall = round(array_sum($ratings) / count($ratings), 2);

        return Review::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'customer_id' => $customerId,
            'quality_rating' => $ratings['quality_rating'],
            'behavior_rating' => $ratings['behavior_rating'],
            'time_rating' => $ratings['time_rating'],
            'communication_rating' => $ratings['communication_rating'],
            'overall_rating' => $overall,
            'comment' => $comment,
        ]);
    }
}
