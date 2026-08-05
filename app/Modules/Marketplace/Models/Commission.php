<?php

namespace App\Modules\Marketplace\Models;

use App\Modules\Orders\Models\Order;
use App\Modules\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['tenant_id', 'order_id', 'order_total', 'rate', 'amount', 'status'];

    protected $casts = [
        'order_total' => 'decimal:2',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
