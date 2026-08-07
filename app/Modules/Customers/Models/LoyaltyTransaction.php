<?php

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['loyalty_account_id', 'type', 'points', 'points_after', 'reference'];

    /**
     * @return BelongsTo<LoyaltyAccount, $this>
     */
    public function loyaltyAccount(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class);
    }
}
