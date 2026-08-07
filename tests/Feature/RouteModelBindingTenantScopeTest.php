<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Tenants\Models\Tenant;

/**
 * Regression coverage for a Phase 5 bug: ResolveTenant was declared in the
 * 'api' middleware group, but Laravel resorts every request's middleware by
 * $middlewarePriority regardless of declaration order - Authenticate and
 * SubstituteBindings are both in that priority list, so SubstituteBindings
 * (which resolves {product}/{order}/etc. route params into models) was
 * running before ResolveTenant ever set tenant context. With RLS enabled,
 * every implicit route-bound tenant-scoped model 404'd for its own owner.
 * These tests only exercise real HTTP requests through the full middleware
 * pipeline - a unit-level TenantContext/RLS test can't catch this class of
 * bug, since it's specifically about *when* ResolveTenant runs relative to
 * framework middleware, not whether the isolation logic itself is correct.
 */
test('a tenant owner can fetch their own product by id through the real HTTP route binding', function () {
    $tenant = Tenant::create(['name' => 'Binding Co', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'binding-co']);
    $owner = User::create(['tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => 'owner-binding@example.test', 'password' => bcrypt('password')]);
    $product = Product::create(['tenant_id' => $tenant->id, 'name' => 'Widget', 'slug' => 'widget-binding', 'price' => 10]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id);
});

test('a tenant owner can fetch their own order by id through the real HTTP route binding', function () {
    $tenant = Tenant::create(['name' => 'Binding Co 2', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'binding-co-2']);
    $owner = User::create(['tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => 'owner-binding-2@example.test', 'password' => bcrypt('password')]);
    $order = Order::create([
        'tenant_id' => $tenant->id, 'user_id' => $owner->id, 'order_number' => 'ORD-BINDING-1', 'status' => 'pending',
        'subtotal' => 10, 'discount_total' => 0, 'shipping_total' => 0, 'tax_total' => 0, 'total' => 10, 'currency' => 'USD',
        'shipping_address' => ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'],
    ]);

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

test('a different tenant cannot fetch a product by guessing its id through the real HTTP route binding', function () {
    $tenantA = Tenant::create(['name' => 'Binding Co A', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'binding-co-a']);
    $tenantB = Tenant::create(['name' => 'Binding Co B', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'binding-co-b']);
    $ownerB = User::create(['tenant_id' => $tenantB->id, 'name' => 'Owner B', 'email' => 'owner-binding-b@example.test', 'password' => bcrypt('password')]);
    $productA = Product::create(['tenant_id' => $tenantA->id, 'name' => 'Widget A', 'slug' => 'widget-a-binding', 'price' => 10]);

    $this->actingAs($ownerB, 'sanctum')
        ->getJson("/api/v1/products/{$productA->id}")
        ->assertNotFound();
});
