<?php

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['wallet_account_id', 'type', 'amount', 'balance_after', 'reference', 'description'];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<WalletAccount, $this>
     */
    public function walletAccount(): BelongsTo
    {
        return $this->belongsTo(WalletAccount::class);
    }
}
