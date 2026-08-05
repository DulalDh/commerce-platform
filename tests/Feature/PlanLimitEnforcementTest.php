<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Tenants\Models\SubscriptionPlan;
use App\Modules\Tenants\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Plan Limit Test Co', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'plan-limit-test',
    ]);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Owner', 'email' => 'owner@plan-limit-test.test',
        'password' => bcrypt('password'),
    ]);
});

test('product creation is blocked once the current plan limit is reached', function () {
    $plan = SubscriptionPlan::create([
        'name' => 'Starter', 'slug' => 'starter-'.uniqid(), 'price' => 9.99, 'limits' => ['products' => 1],
    ]);

    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/subscriptions/subscribe', ['plan_id' => $plan->id])
        ->assertCreated();

    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/products', ['name' => 'First', 'slug' => 'first-'.uniqid(), 'price' => 10])
        ->assertCreated();

    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/products', ['name' => 'Second', 'slug' => 'second-'.uniqid(), 'price' => 10])
        ->assertForbidden();

    expect(Product::where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

test('downgrading to a stricter plan blocks further creation even though the old plan allowed it', function () {
    $generousPlan = SubscriptionPlan::create([
        'name' => 'Pro', 'slug' => 'pro-'.uniqid(), 'price' => 49.99, 'limits' => ['products' => 5],
    ]);
    $strictPlan = SubscriptionPlan::create([
        'name' => 'Starter', 'slug' => 'starter-'.uniqid(), 'price' => 9.99, 'limits' => ['products' => 1],
    ]);

    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/subscriptions/subscribe', ['plan_id' => $generousPlan->id])
        ->assertCreated();

    // Two products fit comfortably under the generous plan's limit of 5.
    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/products', ['name' => 'First', 'slug' => 'first-'.uniqid(), 'price' => 10])
        ->assertCreated();
    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/products', ['name' => 'Second', 'slug' => 'second-'.uniqid(), 'price' => 10])
        ->assertCreated();

    expect(Product::where('tenant_id', $this->tenant->id)->count())->toBe(2);

    // Downgrade: now already 2 over the new limit of 1.
    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/subscriptions/subscribe', ['plan_id' => $strictPlan->id])
        ->assertCreated();

    // The middleware must read the live plan limit at request time, not a
    // cached value from when the tenant subscribed to the generous plan.
    $this->actingAs($this->owner, 'sanctum')
        ->postJson('/api/v1/products', ['name' => 'Third', 'slug' => 'third-'.uniqid(), 'price' => 10])
        ->assertForbidden();

    expect(Product::where('tenant_id', $this->tenant->id)->count())->toBe(2);
});
