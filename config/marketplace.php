<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Commission Rate
    |--------------------------------------------------------------------------
    |
    | Flat rate applied to an order's total when it's confirmed, written to
    | the commissions ledger. A future phase may make this plan-based or
    | per-tenant; kept flat and config-driven for now.
    |
    */

    'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.10),

];
