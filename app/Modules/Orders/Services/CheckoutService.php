<?php

namespace App\Modules\Orders\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Models\Order;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected InventoryService $inventory,
    ) {}

    public function checkout(Cart $cart, array $shippingAddress, ?array $billingAddress, int $userId): Order
    {
        $cart->loadMissing('items.product', 'items.variant');

        if ($cart->items->isEmpty()) {
            throw new InvalidArgumentException('Cannot checkout an empty cart.');
        }

        return DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $userId) {
            $subtotal = $cart->items->sum(fn ($item) => $item->unit_price * $item->quantity);

            $order = Order::create([
                'tenant_id' => $this->tenantContext->id(),
                'user_id' => $userId,
                'order_number' => 'ORD-'.strtoupper(Str::random(10)),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'tenant_id' => $order->tenant_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->variant?->name ?? $item->product->name,
                    'sku' => $item->variant?->sku ?? $item->product->sku,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->unit_price * $item->quantity,
                ]);

                // Reserve stock at checkout time - insufficient stock aborts
                // the whole transaction (order, items, and the deduction
                // already applied all roll back together).
                $inventoryItem = $this->inventory->findOrCreateItem($item->product_id, $item->variant_id);
                $this->inventory->stockOut(
                    $inventoryItem, $item->quantity, reference: $order->order_number, userId: $userId
                );
            }

            $cart->update(['status' => 'converted']);

            return $order->load('items');
        });
    }
}
