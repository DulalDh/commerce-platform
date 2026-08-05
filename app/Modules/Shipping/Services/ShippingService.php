<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStatusMachine;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShippingService
{
    /** @var array<string, string> */
    protected const STATUS_MAP = [
        'pending' => 'pending',
        'picked_up' => 'picked_up',
        'pickup' => 'picked_up',
        'in_transit' => 'in_transit',
        'shipped' => 'in_transit',
        'delivered' => 'delivered',
        'complete' => 'delivered',
        'failed' => 'failed',
        'cancelled' => 'failed',
        'returned' => 'returned',
    ];

    public function __construct(
        protected TenantContext $tenantContext,
        protected ShippingProviderResolver $resolver,
        protected OrderStatusMachine $statusMachine,
    ) {}

    public function calculateRate(string $provider, array $params): array
    {
        return $this->resolver->resolve($provider)->calculateRate($params);
    }

    public function createShipment(Order $order, string $provider): Shipment
    {
        $result = $this->resolver->resolve($provider)->createShipment($order);

        return Shipment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'provider' => $provider,
            'tracking_number' => $result['tracking_number'],
            'status' => 'pending',
            'rate' => $result['rate'],
            'currency' => $result['currency'],
        ]);
    }

    /**
     * Same idempotency shape as PaymentService::handleWebhook - couriers
     * retry status callbacks too, and a delivered event must only ever
     * transition the order once.
     */
    public function handleWebhook(string $provider, Request $request): void
    {
        $providerImpl = $this->resolver->resolve($provider);

        if (! $providerImpl->verifyWebhookSignature($request)) {
            throw new InvalidArgumentException('Invalid webhook signature.');
        }

        $payload = $providerImpl->parseWebhookPayload($request);
        $normalizedStatus = self::STATUS_MAP[strtolower($payload->status)] ?? 'in_transit';

        DB::transaction(function () use ($provider, $payload, $normalizedStatus) {
            $shipment = Shipment::withoutGlobalScopes()
                ->where('provider', $provider)
                ->where('tracking_number', $payload->trackingNumber)
                ->lockForUpdate()
                ->first();

            if (! $shipment) {
                return;
            }

            // Terminal states don't get overwritten by a late/duplicate
            // callback reporting an earlier status.
            if (in_array($shipment->status, ['delivered', 'failed', 'returned'], true)) {
                return;
            }

            if ($shipment->status === $normalizedStatus) {
                return;
            }

            try {
                $shipment->update(['status' => $normalizedStatus]);
            } catch (UniqueConstraintViolationException) {
                return;
            }

            if ($normalizedStatus === 'delivered') {
                $order = Order::withoutGlobalScopes()->find($shipment->order_id);

                if ($order && $order->status === 'shipped') {
                    $this->statusMachine->transition($order, 'delivered', 'Delivered via '.$provider);
                }
            }
        });
    }
}
