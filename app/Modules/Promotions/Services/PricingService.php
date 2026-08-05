<?php

namespace App\Modules\Promotions\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Promotions\Models\FlashSale;

class PricingService
{
    /**
     * The price CartService should charge right now for this product,
     * factoring in an active flash sale if one applies. Falls back to the
     * product's own price/sale_price when no flash sale is running.
     */
    public function effectivePrice(Product $product): float
    {
        $basePrice = (float) ($product->sale_price ?? $product->price);

        $activeSale = FlashSale::where('status', 'active')
            ->whereHas('products', fn ($q) => $q->where('products.id', $product->id))
            ->first();

        if (! $activeSale) {
            return $basePrice;
        }

        $discounted = $basePrice * (1 - (float) $activeSale->discount_percentage / 100);

        return round(max($discounted, 0), 2);
    }
}
