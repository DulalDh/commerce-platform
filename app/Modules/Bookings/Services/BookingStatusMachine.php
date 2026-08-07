<?php

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Jobs\NotifyBookingStatusChangedJob;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Services\LoyaltyService;
use InvalidArgumentException;

class BookingStatusMachine
{
    public function __construct(protected LoyaltyService $loyalty) {}

    /** @var array<string, string[]> */
    protected const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['assigned', 'cancelled'],
        'assigned' => ['on_the_way', 'cancelled'],
        'on_the_way' => ['started', 'cancelled'],
        'started' => ['completed', 'cancelled'],
        'completed' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function transition(Booking $booking, string $to, ?string $note = null, ?int $userId = null): Booking
    {
        $from = $booking->status;

        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException("Cannot transition booking from [{$from}] to [{$to}].");
        }

        $booking->status = $to;

        if ($to === 'cancelled' && $note) {
            $booking->cancellation_reason = $note;
        }

        $booking->save();

        $booking->statusHistories()->create([
            'tenant_id' => $booking->tenant_id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'changed_by' => $userId,
        ]);

        NotifyBookingStatusChangedJob::dispatch((string) $booking->id, $to);

        if ($to === 'completed') {
            $total = (float) $booking->price + (float) $booking->emergency_charge;
            $this->loyalty->earn($booking->customer_id, $total, (string) $booking->id);
        }

        return $booking;
    }
}
