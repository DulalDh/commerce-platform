<?php

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\Holiday;
use App\Modules\Services\Models\Staff;
use InvalidArgumentException;

/**
 * Fast, friendly pre-check before attempting to create a booking. This is
 * NOT what actually prevents double-booking under concurrency - it's a
 * plain SELECT with an inherent race window between check and insert. The
 * real guarantee is the partial unique index on
 * (tenant_id, scheduled_date, scheduled_slot_start) added in the bookings
 * migration, which BookingService relies on as the backstop.
 */
class AvailabilityCheckService
{
    public function ensureSlotIsFree(string $tenantId, string $date, string $slotStart): void
    {
        $isHoliday = Holiday::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('date', $date)
            ->exists();

        if ($isHoliday) {
            throw new InvalidArgumentException('This provider is closed on the requested date.');
        }

        $taken = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('scheduled_date', $date)
            ->where('scheduled_slot_start', $slotStart)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('This slot is no longer available.');
        }
    }

    public function ensureStaffIsAvailable(Staff $staff, string $date): void
    {
        if ($staff->isOnLeave($date)) {
            throw new InvalidArgumentException('This staff member is on leave on the requested date.');
        }
    }
}
