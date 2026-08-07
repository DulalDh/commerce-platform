<?php

namespace App\Modules\Bookings\Jobs;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyBookingStatusChangedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $bookingId, public string $toStatus) {}

    public function handle(NotificationService $notifications): void
    {
        $booking = Booking::withoutGlobalScopes()->find($this->bookingId);

        if (! $booking) {
            return;
        }

        $notifications->send(
            $booking->customer_id,
            'booking_status_changed',
            ['status' => $this->toStatus, 'booking_id' => $booking->id],
            ['in_app', 'sms'],
        );
    }
}
