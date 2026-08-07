<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Emergency Booking Surcharge Rate
    |--------------------------------------------------------------------------
    |
    | Percentage (as a decimal) added on top of a service's normal price
    | when a booking is flagged emergency, unless the service's own pricing
    | model is already 'emergency' (in which case its price already
    | reflects the emergency rate).
    |
    */

    'emergency_surcharge_rate' => (float) env('BOOKINGS_EMERGENCY_SURCHARGE_RATE', 0.5),

];
