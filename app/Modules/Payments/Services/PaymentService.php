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

        // Only gateways that explicitly opt in (COD) complete immediately -
        // Bank Transfer also has no redirect step but must stay pending
        // until a staff member manually confirms the funds arrived.
        if (($result['auto_complete'] ?? false) === true) {
            $this->completePayment($payment, gatewayReference: $gateway.'-'.$payment->idempotency_key, raw: []);
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

    /**
     * Staff-initiated confirmation for gateways with no webhook (Bank
     * Transfer) - the human checking the bank statement is standing in
     * for what would otherwise be an automated callback.
     */
    public function confirmManual(Payment $payment, ?int $userId = null): void
    {
        if ($payment->gateway !== 'bank_transfer') {
            throw new InvalidArgumentException('Only Bank Transfer payments can be manually confirmed.');
        }

        if ($payment->status !== 'pending') {
            return;
        }

        $this->completePayment($payment, gatewayReference: 'manual-'.$payment->idempotency_key, raw: ['confirmed_by' => $userId]);
    }

    protected function completePayment(Payment $payment, string $gatewayReference, array $raw): void
    {
        try {
            // Wrapped in its own transaction (a SAVEPOINT, since callers
            // already run inside one) - Postgres poisons the whole enclosing
            // transaction on a unique-violation even when caught, so without
            // this the caller's subsequent queries would fail with
            // "current transaction is aborted".
            DB::transaction(fn () => $payment->update([
                'status' => 'completed',
                'gateway_reference' => $gatewayReference,
                'raw_payload' => $raw,
            ]));
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
