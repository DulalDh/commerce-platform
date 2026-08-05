<?php

namespace App\Modules\Shipping\Contracts;

readonly class ShippingWebhookPayload
{
    public function __construct(
        public string $trackingNumber,
        public string $status,
        public array $raw,
    ) {}
}
