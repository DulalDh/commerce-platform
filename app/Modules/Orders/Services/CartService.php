<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Cart;
use App\Modules\Promotions\Services\PricingService;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PricingService $pricingService,
    ) {}

    public function activeCartFor(int $userId): Cart
    {
        return Cart::firstOrCreate(
            ['tenant_id' => $this->tenantContext->id(), 'user_id' => $userId, 'status' => 'active'],
        );
    }

    public function addItem(Cart $cart, string $productId, ?string $variantId, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $productId, $variantId, $quantity) {
            $product = Product::findOrFail($productId);

            // Flash sales apply at the product level, not per-variant.
            $unitPrice = $this->pricingService->effectivePrice($product);

            if ($variantId) {
                $variant = ProductVariant::where('product_id', $productId)->findOrFail($variantId);
                $unitPrice = $variant->sale_price ?? $variant->price ?? $unitPrice;
            }

            $item = $cart->items()->firstOrNew([
                'product_id' => $productId,
                'variant_id' => $variantId,
            ]);

            $item->tenant_id = $cart->tenant_id;
            $item->cart_id = $cart->id;
            $item->unit_price = $unitPrice;
            $item->quantity = ($item->quantity ?? 0) + $quantity;
            $item->save();

            return $cart->fresh('items');
        });
    }

    public function updateItemQuantity(Cart $cart, string $itemId, int $quantity): Cart
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive; remove the item instead of setting it to zero.');
        }

        $item = $cart->items()->findOrFail($itemId);
        $item->update(['quantity' => $quantity]);

        return $cart->fresh('items');
    }

    public function removeItem(Cart $cart, string $itemId): Cart
    {
        $cart->items()->findOrFail($itemId)->delete();

        return $cart->fresh('items');
    }
}
