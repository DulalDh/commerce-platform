<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Marketplace\Models\Commission;
use App\Modules\Marketplace\Services\CommissionService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\CartService;
use App\Modules\Orders\Services\CheckoutService;
use App\Modules\Orders\Services\OrderStatusMachine;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Support\TenantContext;

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Commission Test Co', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'commission-test',
    ]);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Owner', 'email' => 'owner@commission-test.test',
        'password' => bcrypt('password'),
    ]);
    app(TenantContext::class)->set($this->tenant);

    $this->product = Product::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Widget', 'slug' => 'widget', 'price' => 25,
    ]);

    $inventory = app(InventoryService::class);
    $inventory->stockIn($inventory->findOrCreateItem($this->product->id), 100);
});

test('the sum of commission ledger entries reconciles exactly against confirmed order revenue', function () {
    $statusMachine = app(OrderStatusMachine::class);
    $rate = (float) config('marketplace.commission_rate');

    // Three separate orders, confirmed via the same path the payment
    // webhook uses (pending -> confirmed), each should produce exactly
    // one commission row.
    $orderTotals = [];
    foreach ([1, 2, 1] as $quantity) {
        $cart = app(CartService::class)->activeCartFor($this->owner->id);
        $cart = app(CartService::class)->addItem($cart, $this->product->id, null, $quantity);
        $order = app(CheckoutService::class)->checkout(
            $cart, ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'], null, $this->owner->id
        );
        $statusMachine->transition($order, 'confirmed');
        $orderTotals[] = (float) $order->fresh()->total;
    }

    $expectedTotalRevenue = array_sum($orderTotals);
    $expectedTotalCommission = round($expectedTotalRevenue * $rate, 2);

    $actualTotalCommission = (float) Commission::where('tenant_id', $this->tenant->id)->sum('amount');
    $actualRevenueFromLedger = (float) Commission::where('tenant_id', $this->tenant->id)->sum('order_total');

    expect(Commission::where('tenant_id', $this->tenant->id)->count())->toBe(3);
    expect($actualRevenueFromLedger)->toBe($expectedTotalRevenue);
    expect(round($actualTotalCommission, 2))->toBe($expectedTotalCommission);

    // Cross-check against the live orders table independently of the
    // ledger - proves the ledger isn't just internally consistent with
    // itself but actually matches what was charged.
    $liveOrderRevenue = (float) Order::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->whereNotIn('status', ['cancelled', 'refunded'])
        ->sum('total');

    expect($liveOrderRevenue)->toBe($expectedTotalRevenue);
});

test('confirming the same order twice never creates a second commission row', function () {
    $statusMachine = app(OrderStatusMachine::class);

    $cart = app(CartService::class)->activeCartFor($this->owner->id);
    $cart = app(CartService::class)->addItem($cart, $this->product->id, null, 1);
    $order = app(CheckoutService::class)->checkout(
        $cart, ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'], null, $this->owner->id
    );

    $statusMachine->transition($order, 'confirmed');

    // A second call to the commission service directly (simulating some
    // future code path re-triggering it) must not double the ledger.
    app(CommissionService::class)->calculateForOrder($order->fresh());

    expect(Commission::where('order_id', $order->id)->count())->toBe(1);
});
