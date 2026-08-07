<?php

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Services\CartService;
use App\Modules\Orders\Services\CheckoutService;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Services\ShippingService;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function fakeRedxWebhookRequest(string $trackingNumber, string $status): Request
{
    $body = http_build_query(['tracking_id' => $trackingNumber, 'status' => $status]);
    $secret = (string) config('services.redx.api_secret');
    $signature = hash_hmac('sha256', $body, $secret);

    $request = Request::create('/api/v1/shipping/webhook/redx', 'POST', [], [], [], [], $body);
    $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
    $request->headers->set('X-Signature', $signature);
    $request->request->add(['tracking_id' => $trackingNumber, 'status' => $status]);

    return $request;
}

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Shipping Test Tenant', 'type' => 'merchant', 'status' => 'active', 'subdomain' => 'shipping-test',
    ]);
    $this->owner = User::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Owner', 'email' => 'owner-shipping@example.test',
        'password' => bcrypt('password'),
    ]);
    app(TenantContext::class)->set($this->tenant);

    $this->product = Product::create([
        'tenant_id' => $this->tenant->id, 'name' => 'Widget', 'slug' => 'widget-ship', 'price' => 10,
    ]);
    $item = app(InventoryService::class)->findOrCreateItem($this->product->id);
    app(InventoryService::class)->stockIn($item, 100);

    $cart = app(CartService::class)->activeCartFor($this->owner->id);
    $cart = app(CartService::class)->addItem($cart, $this->product->id, null, 1);
    $this->order = app(CheckoutService::class)->checkout(
        $cart, ['line1' => 'A St', 'city' => 'City', 'country' => 'Country'], null, $this->owner->id
    );

    $this->shipment = Shipment::create([
        'tenant_id' => $this->tenant->id, 'order_id' => $this->order->id, 'provider' => 'redx',
        'tracking_number' => 'REDX-TEST-001', 'status' => 'pending', 'rate' => 55, 'currency' => 'BDT',
    ]);
});

test('a delivered webhook marks the shipment delivered exactly once, even without an authenticated tenant context', function () {
    // The webhook route is public and unauthenticated - simulate that by
    // clearing any tenant context, the same as a real courier callback.
    app(TenantContext::class)->clear();
    DB::statement("select set_config('app.current_tenant_id', '', false)");

    app(ShippingService::class)->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'delivered'));

    expect(Shipment::withoutGlobalScopes()->find($this->shipment->id)->status)->toBe('delivered');
});

test('a retried delivered webhook does not re-transition an already-delivered shipment', function () {
    app(TenantContext::class)->clear();
    DB::statement("select set_config('app.current_tenant_id', '', false)");
    $service = app(ShippingService::class);

    $service->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'delivered'));
    $service->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'delivered'));
    $service->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'delivered'));

    expect(Shipment::withoutGlobalScopes()->find($this->shipment->id)->status)->toBe('delivered');
});

test('a late pending callback cannot overwrite an already-terminal delivered shipment', function () {
    app(TenantContext::class)->clear();
    DB::statement("select set_config('app.current_tenant_id', '', false)");
    $service = app(ShippingService::class);

    $service->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'delivered'));
    $service->handleWebhook('redx', fakeRedxWebhookRequest('REDX-TEST-001', 'in_transit'));

    expect(Shipment::withoutGlobalScopes()->find($this->shipment->id)->status)->toBe('delivered');
});

test('a webhook for an unknown tracking number is ignored, not errored', function () {
    app(TenantContext::class)->clear();
    DB::statement("select set_config('app.current_tenant_id', '', false)");

    app(ShippingService::class)->handleWebhook('redx', fakeRedxWebhookRequest('REDX-UNKNOWN', 'delivered'));

    expect(Shipment::withoutGlobalScopes()->find($this->shipment->id)->status)->toBe('pending');
});

test('a shipping webhook with an invalid signature is rejected before any shipment lookup', function () {
    app(TenantContext::class)->clear();
    DB::statement("select set_config('app.current_tenant_id', '', false)");

    $request = Request::create('/api/v1/shipping/webhook/redx', 'POST', [], [], [], [], 'tracking_id=x&status=delivered');
    $request->headers->set('X-Signature', 'not-a-valid-signature');

    expect(fn () => app(ShippingService::class)->handleWebhook('redx', $request))
        ->toThrow(InvalidArgumentException::class);

    DB::statement("select set_config('app.current_tenant_id', 'bypass', false)");
    expect(Shipment::withoutGlobalScopes()->find($this->shipment->id)->status)->toBe('pending');
});
