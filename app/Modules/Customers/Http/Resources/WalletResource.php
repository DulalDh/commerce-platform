<?php

namespace App\Modules\Customers\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WalletResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'balance' => $this->balance,
            'currency' => $this->currency,
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($t) => [
                'type' => $t->type,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at,
            ])),
        ];
    }
}
