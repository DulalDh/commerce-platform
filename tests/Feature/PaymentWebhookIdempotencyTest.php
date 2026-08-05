<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\CartService;
use App\Modules\Orders\Services\CheckoutService;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Http\Request;

function makeTenantWithOwner(string $subdomain): array
{
    $tenant = Tenant::create([
        'name' => "Tenant {$subdomain}", 'type' => 'merchant', 'status' => 'active', 'subdomain' => $subdomain,
    ]);
    $owner = User::create([
        'tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => "owner-{$subdomain}@example.test",
        'password' => bcrypt('password'),
    ]);

    return [$tenant, $owner];
}

function fakeSslcommerzWebhookRequest(string $idempotencyKey, string $gatewayReference): Request
{
    $body = http_build_query([
        'tran_id' => $idempotencyKey,
        'bank_tran_id' => $gatewayReference,
        'status' => 'VALID',
    ]);

    $secret = (string) config('services.sslcommerz.store_password');
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/api/v1/payments/webhook/sslcommerz', 'POST', [], [], [], [], $body);
    $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
    $request->headers->set('X-Signature', $signature);
    $request->request->add([
        'tran_id' => $idempotencyKey,
        'bank_tran_id' => $gatewayReference,
        'status' => 'VALID',
    ]);

    return $request;
}

beforeEach(function () {
    [$this->tenant, $this->owner] = makeTenantWithOwner('checkout-test');
    app(TenantContext::class)->set($this->tenant);

    $this->product = Product::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Widget', 'slug' => 'widget', 'price' => 10,
    ]);

    $inventoryItem = app(InventoryService::class)
        ->findOrCreateItem($this->product->id);
    app(InventoryService::class)->stockIn($inventoryItem, 100);

    $cart = app(CartService::class)->activeCartFor($this->owner->id);
    $cart = app(CartService::class)->addItem($cart, $this->product->id, null, 1);
    $this->order = app(CheckoutService::class)->checkout(
        $cart, ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'], null, $this->owner->id
    );
});

test('a successful webhook confirms the order exactly once', function () {
    $payment = Payment::create([
        'tenant_id' => $this->tenant->id, 'order_id' => $this->order->id, 'gateway' => 'sslcommerz',
        'amount' => $this->order->total, 'currency' => 'USD', 'status' => 'pending',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $request = fakeSslcommerzWebhookRequest($payment->idempotency_key, 'BANKTX-001');
    app(PaymentService::class)->handleWebhook('sslcommerz', $request);

    expect($payment->fresh()->status)->toBe('completed');
    expect(Order::withoutGlobalScopes()->find($this->order->id)->status)->toBe('confirmed');
    expect($this->order->fresh()->statusHistories()->count())->toBe(1);
});

test('a retried webhook delivery does not double-confirm or double-fulfill the order', function () {
    $payment = Payment::create([
        'tenant_id' => $this->tenant->id, 'order_id' => $this->order->id, 'gateway' => 'sslcommerz',
        'amount' => $this->order->total, 'currency' => 'USD', 'status' => 'pending',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $service = app(PaymentService::class);

    // Same webhook payload delivered three times, as a real gateway would
    // on timeout/ambiguous-response retries.
    $service->handleWebhook('sslcommerz', fakeSslcommerzWebhookRequest($payment->idempotency_key, 'BANKTX-002'));
    $service->handleWebhook('sslcommerz', fakeSslcommerzWebhookRequest($payment->idempotency_key, 'BANKTX-002'));
    $service->handleWebhook('sslcommerz', fakeSslcommerzWebhookRequest($payment->idempotency_key, 'BANKTX-002'));

    expect(Payment::withoutGlobalScopes()->where('order_id', $this->order->id)->count())->toBe(1);
    expect($this->order->fresh()->statusHistories()->count())->toBe(1);
    expect(Order::withoutGlobalScopes()->find($this->order->id)->status)->toBe('confirmed');
});

test('a webhook with an unrecognized idempotency key is ignored, not errored', function () {
    $request = fakeSslcommerzWebhookRequest((string) Str::uuid(), 'BANKTX-UNKNOWN');

    app(PaymentService::class)->handleWebhook('sslcommerz', $request);

    expect(Order::withoutGlobalScopes()->find($this->order->id)->status)->toBe('pending');
});

test('a webhook with an invalid signature is rejected before any payment lookup', function () {
    $request = Request::create('/api/v1/payments/webhook/sslcommerz', 'POST', [], [], [], [], 'tran_id=x&bank_tran_id=y&status=VALID');
    $request->headers->set('X-Signature', 'not-a-valid-signature');

    expect(fn () => app(PaymentService::class)->handleWebhook('sslcommerz', $request))
        ->toThrow(InvalidArgumentException::class);
});

test('checkout fails cleanly and rolls back when stock is insufficient', function () {
    $cart = app(CartService::class)->activeCartFor($this->owner->id);
    $cart = app(CartService::class)->addItem($cart, $this->product->id, null, 999999);

    expect(fn () => app(CheckoutService::class)->checkout(
        $cart, ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'], null, $this->owner->id
    ))->toThrow(InvalidArgumentException::class);

    // The order from this failed checkout attempt must not exist - the
    // whole transaction (order + items + stock deduction) rolled back together.
    expect(Order::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});
