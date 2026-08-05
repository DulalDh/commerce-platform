<?php

namespace App\Modules\Payments\Contracts;

readonly class WebhookPayload
{
    public function __construct(
        public string $gatewayReference,
        public string $idempotencyKey,
        public bool $successful,
        public array $raw,
    ) {}
}
