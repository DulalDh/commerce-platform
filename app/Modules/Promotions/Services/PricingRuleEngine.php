<?php

namespace App\Modules\Promotions\Services;

use App\Modules\Orders\Models\CartItem;
use App\Modules\Promotions\Models\PromotionRule;
use Illuminate\Support\Collection;

/**
 * Computes extra discount from bundle/BOGO rules against a cart's items.
 */
class PricingRuleEngine
{
    /**
     * @param  Collection<int, CartItem>  $cartItems
     */
    public function calculateDiscount(Collection $cartItems): float
    {
        $quantities = $cartItems->pluck('quantity', 'product_id');
        $unitPrices = $cartItems->pluck('unit_price', 'product_id');

        $discount = 0.0;

        foreach (PromotionRule::where('is_active', true)->get() as $rule) {
            $discount += match ($rule->type) {
                'bundle' => $this->bundleDiscount($rule->config, $quantities, $unitPrices),
                'bogo' => $this->bogoDiscount($rule->config, $quantities, $unitPrices),
                default => 0.0,
            };
        }

        return round($discount, 2);
    }

    protected function bundleDiscount(array $config, Collection $quantities, Collection $unitPrices): float
    {
        $productIds = $config['product_ids'] ?? [];
        $bundlePrice = (float) ($config['bundle_price'] ?? 0);

        if (empty($productIds)) {
            return 0.0;
        }

        // How many complete bundles can this cart form? Limited by whichever
        // required product has the fewest units in the cart.
        $completeBundles = collect($productIds)->map(fn ($id) => $quantities->get($id, 0))->min();

        if ($completeBundles <= 0) {
            return 0.0;
        }

        $fullPrice = collect($productIds)->sum(fn ($id) => (float) $unitPrices->get($id, 0));

        return max(0.0, ($fullPrice - $bundlePrice) * $completeBundles);
    }

    protected function bogoDiscount(array $config, Collection $quantities, Collection $unitPrices): float
    {
        $buyProductId = $config['buy_product_id'] ?? null;
        $getProductId = $config['get_product_id'] ?? null;
        $buyQty = (int) ($config['buy_qty'] ?? 1);
        $getQty = (int) ($config['get_qty'] ?? 1);
        $getDiscountPercentage = (float) ($config['get_discount_percentage'] ?? 100);

        if (! $buyProductId || ! $getProductId) {
            return 0.0;
        }

        $buyQtyInCart = $quantities->get($buyProductId, 0);
        $getQtyInCart = $quantities->get($getProductId, 0);

        if ($buyQtyInCart < $buyQty || $getQtyInCart <= 0) {
            return 0.0;
        }

        $qualifyingSets = intdiv($buyQtyInCart, $buyQty);
        $freeUnits = min($qualifyingSets * $getQty, $getQtyInCart);

        return $freeUnits * (float) $unitPrices->get($getProductId, 0) * ($getDiscountPercentage / 100);
    }
}
