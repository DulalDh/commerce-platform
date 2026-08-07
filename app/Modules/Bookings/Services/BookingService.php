<?php

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingService
{
    public function __construct(
        protected AvailabilityCheckService $availability,
        protected BookingStatusMachine $statusMachine,
        protected EmergencyChargeService $emergencyCharge,
    ) {}

    public function create(
        int $customerId,
        string $serviceId,
        string $date,
        string $slotStart,
        string $slotEnd,
        array $address,
        bool $isEmergency = false,
        ?string $notes = null,
    ): Booking {
        // The customer booking a service has no tenant context of their own
        // (they're not staff of the provider) - the tenant this booking
        // belongs to is determined by the service itself, not by whoever
        // is making the request.
        $service = Service::findOrFail($serviceId);
        $tenantId = $service->tenant_id;

        $this->availability->ensureSlotIsFree($tenantId, $date, $slotStart);

        $emergencyCharge = $this->emergencyCharge->calculate($service, $isEmergency);

        try {
            return DB::transaction(fn () => Booking::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'service_id' => $service->id,
                'status' => 'pending',
                'scheduled_date' => $date,
                'scheduled_slot_start' => $slotStart,
                'scheduled_slot_end' => $slotEnd,
                'address' => $address,
                'is_emergency' => $isEmergency,
                'price' => $service->price,
                'emergency_charge' => $emergencyCharge,
                'notes' => $notes,
            ]));
        } catch (QueryException $e) {
            // Postgres error code 23505 = unique_violation. A concurrent
            // request won the race for this exact slot between our
            // pre-check above and this insert - the partial unique index
            // on (tenant_id, scheduled_date, scheduled_slot_start) is what
            // actually caught it.
            if ($e->getCode() === '23505') {
                throw new InvalidArgumentException('This slot is no longer available.');
            }

            throw $e;
        }
    }

    public function accept(Booking $booking, ?int $userId = null): Booking
    {
        return $this->statusMachine->transition($booking, 'accepted', userId: $userId);
    }

    public function reject(Booking $booking, ?string $reason = null, ?int $userId = null): Booking
    {
        return $this->statusMachine->transition($booking, 'cancelled', $reason, $userId);
    }

    public function assign(Booking $booking, string $staffId, ?int $userId = null): Booking
    {
        if ($booking->status !== 'accepted') {
            throw new InvalidArgumentException("Booking must be [accepted] for this action, currently [{$booking->status}].");
        }

        $staff = Staff::findOrFail($staffId);
        $this->availability->ensureStaffIsAvailable($staff, $booking->scheduled_date->toDateString());

        $booking->staff_id = $staff->id;
        $booking->save();

        return $this->statusMachine->transition($booking, 'assigned', userId: $userId);
    }

    /**
     * Generic transition for the remaining lifecycle steps
     * (on_the_way, started, completed, cancelled, refunded) that don't
     * need extra business logic beyond the guarded transition itself.
     */
    public function transition(Booking $booking, string $to, ?string $note = null, ?int $userId = null): Booking
    {
        return $this->statusMachine->transition($booking, $to, $note, $userId);
    }
}
