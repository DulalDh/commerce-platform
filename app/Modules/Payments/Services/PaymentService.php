<?php

namespace App\Modules\Payments\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStatusMachine;
use App\Modules\Payments\Models\Payment;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PaymentGatewayResolver $resolver,
        protected OrderStatusMachine $statusMachine,
    ) {}

    public function initiate(Order $order, string $gateway): array
    {
        $payment = Payment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'gateway' => $gateway,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $result = $this->resolver->resolve($gateway)->initiate($order, $payment);

        // COD (and any gateway with no redirect step) completes immediately -
        // there's no webhook coming, so the completion has to happen here.
        if (($result['requires_redirect'] ?? true) === false) {
            $this->completePayment($payment, gatewayReference: 'cod-'.$payment->idempotency_key, raw: []);
        }

        return ['payment_id' => $payment->id, ...$result];
    }

    /**
     * Webhooks retry on timeout/ambiguous response, and gateways don't
     * dedupe on their end - this method is the idempotency boundary. It
     * must be safe to call an arbitrary number of times with the same
     * payload and only ever fulfill the order once.
     */
    public function handleWebhook(string $gateway, Request $request): void
    {
        $gatewayImpl = $this->resolver->resolve($gateway);

        if (! $gatewayImpl->verifyWebhookSignature($request)) {
            throw new InvalidArgumentException('Invalid webhook signature.');
        }

        $payload = $gatewayImpl->parseWebhookPayload($request);

        DB::transaction(function () use ($payload) {
            $payment = Payment::withoutGlobalScopes()
                ->where('idempotency_key', $payload->idempotencyKey)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                // Unknown idempotency key - nothing we initiated, ignore rather
                // than throw, since gateways retry on any non-2xx response.
                return;
            }

            // Already processed (by an earlier delivery of this same webhook,
            // or a concurrent one that beat this transaction to the lock) -
            // no-op. This is the core of the idempotency guarantee: fulfillment
            // (order confirmation, stock, etc) only ever happens once per
            // payment regardless of how many times the webhook fires.
            if ($payment->status !== 'pending') {
                return;
            }

            if ($payload->successful) {
                $this->completePayment($payment, $payload->gatewayReference, $payload->raw);
            } else {
                $payment->update([
                    'status' => 'failed',
                    'gateway_reference' => $payload->gatewayReference,
                    'raw_payload' => $payload->raw,
                ]);
            }
        });
    }

    protected function completePayment(Payment $payment, string $gatewayReference, array $raw): void
    {
        try {
            $payment->update([
                'status' => 'completed',
                'gateway_reference' => $gatewayReference,
                'raw_payload' => $raw,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another payment already claimed this gateway_reference - a
            // duplicate webhook delivery racing a concurrent one. The other
            // transaction is the one that gets to fulfill; this one backs off.
            return;
        }

        $order = Order::withoutGlobalScopes()->find($payment->order_id);

        if ($order && $order->status === 'pending') {
            $this->statusMachine->transition($order, 'confirmed', 'Payment completed via '.$payment->gateway);
        }
    }
}
