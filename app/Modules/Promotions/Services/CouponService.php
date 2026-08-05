<?php

namespace App\Modules\Promotions\Services;

use App\Modules\Promotions\Models\Coupon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CouponService
{
    public function validate(string $code, float $subtotal): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            throw new InvalidArgumentException('Coupon is invalid or inactive.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new InvalidArgumentException('Coupon is not active yet.');
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw new InvalidArgumentException('Coupon has expired.');
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            throw new InvalidArgumentException('Coupon usage limit reached.');
        }

        if ($subtotal < $coupon->min_order_amount) {
            throw new InvalidArgumentException("Order must be at least {$coupon->min_order_amount} to use this coupon.");
        }

        return $coupon;
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->type === 'percentage'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Increments usage_count atomically and re-checks the limit under lock,
     * so two concurrent checkouts using the last remaining use of a
     * limited coupon can't both succeed.
     */
    public function redeem(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            $locked = Coupon::withoutGlobalScopes()->whereKey($coupon->id)->lockForUpdate()->first();

            if ($locked->usage_limit !== null && $locked->usage_count >= $locked->usage_limit) {
                throw new InvalidArgumentException('Coupon usage limit reached.');
            }

            $locked->increment('usage_count');
        });
    }
}
