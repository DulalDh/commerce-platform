<?php

namespace App\Modules\Customers\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class LoyaltyResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'points_balance' => $this->points_balance,
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($t) => [
                'type' => $t->type,
                'points' => $t->points,
                'points_after' => $t->points_after,
                'created_at' => $t->created_at,
            ])),
        ];
    }
}
