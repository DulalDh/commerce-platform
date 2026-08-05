<?php

namespace App\Modules\Payments\Services\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Contracts\WebhookPayload;
use App\Modules\Payments\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * No third party to call back - the customer wires money directly to the
 * platform's bank account, and a staff member manually confirms receipt
 * (see PaymentController::confirmManual). Unlike COD, this must NOT
 * auto-complete at initiate() time - the payment hasn't actually been
 * received yet, just requested.
 */
class BankTransferGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, Payment $payment): array
    {
        return [
            'requires_redirect' => false,
            'auto_complete' => false,
            'bank_details' => [
                'account_name' => Config::get('services.bank_transfer.account_name'),
                'account_number' => Config::get('services.bank_transfer.account_number'),
                'bank_name' => Config::get('services.bank_transfer.bank_name'),
                'routing_number' => Config::get('services.bank_transfer.routing_number'),
                'reference' => $payment->idempotency_key,
            ],
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return false;
    }

    public function parseWebhookPayload(Request $request): WebhookPayload
    {
        throw new \LogicException('Bank Transfer has no webhook - confirm manually via PUT /payments/{payment}/confirm.');
    }
}
