<?php

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Models\LoyaltyAccount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoyaltyService
{
    const POINTS_PER_CURRENCY_UNIT = 1;

    public function earn(int $customerId, float $amountSpent, ?string $reference = null): void
    {
        $points = (int) floor($amountSpent * self::POINTS_PER_CURRENCY_UNIT);

        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($customerId, $points, $reference) {
            $account = LoyaltyAccount::firstOrCreate(['customer_id' => $customerId]);
            $locked = LoyaltyAccount::whereKey($account->id)->lockForUpdate()->first();

            $newBalance = $locked->points_balance + $points;
            $locked->update(['points_balance' => $newBalance]);

            $locked->transactions()->create([
                'type' => 'earned',
                'points' => $points,
                'points_after' => $newBalance,
                'reference' => $reference,
            ]);
        });
    }

    public function redeem(int $customerId, int $points, ?string $reference = null): void
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Redemption points must be positive.');
        }

        DB::transaction(function () use ($customerId, $points, $reference) {
            $account = LoyaltyAccount::firstOrCreate(['customer_id' => $customerId]);
            $locked = LoyaltyAccount::whereKey($account->id)->lockForUpdate()->first();

            if ($locked->points_balance < $points) {
                throw new InvalidArgumentException('Insufficient loyalty points.');
            }

            $newBalance = $locked->points_balance - $points;
            $locked->update(['points_balance' => $newBalance]);

            $locked->transactions()->create([
                'type' => 'redeemed',
                'points' => -$points,
                'points_after' => $newBalance,
                'reference' => $reference,
            ]);
        });
    }
}
