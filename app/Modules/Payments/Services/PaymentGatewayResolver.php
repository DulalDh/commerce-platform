<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Services\Gateways\BkashGateway;
use App\Modules\Payments\Services\Gateways\CashOnDeliveryGateway;
use App\Modules\Payments\Services\Gateways\SslcommerzGateway;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    protected const GATEWAYS = [
        'sslcommerz' => SslcommerzGateway::class,
        'bkash' => BkashGateway::class,
        'cod' => CashOnDeliveryGateway::class,
    ];

    public function resolve(string $gateway): PaymentGatewayInterface
    {
        $class = self::GATEWAYS[$gateway] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unknown payment gateway [{$gateway}].");
        }

        return app($class);
    }
}
