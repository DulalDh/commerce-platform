<?php

namespace App\Modules\Shipping\Models;

use App\Modules\Orders\Models\Order;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'order_id', 'provider', 'tracking_number', 'status', 'rate', 'currency'];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
