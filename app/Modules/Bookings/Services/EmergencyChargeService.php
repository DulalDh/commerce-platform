<?php

namespace App\Modules\Bookings\Services;

use App\Modules\Services\Models\Service;

class EmergencyChargeService
{
    /**
     * If the service's own pricing model is already 'emergency', its price
     * already reflects the emergency rate - no additional surcharge. For
     * any other pricing model, an emergency request adds a configurable
     * percentage surcharge on top of the normal price.
     */
    public function calculate(Service $service, bool $isEmergency): float
    {
        if (! $isEmergency || $service->pricing_model === 'emergency') {
            return 0.0;
        }

        $rate = (float) config('bookings.emergency_surcharge_rate');

        return round((float) $service->price * $rate, 2);
    }
}
