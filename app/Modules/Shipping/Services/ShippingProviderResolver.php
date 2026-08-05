<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Shipping\Contracts\ShippingProviderInterface;
use App\Modules\Shipping\Services\Providers\PathaoProvider;
use App\Modules\Shipping\Services\Providers\RedxProvider;
use App\Modules\Shipping\Services\Providers\SteadfastProvider;
use App\Modules\Shipping\Services\Providers\SundarbanProvider;
use InvalidArgumentException;

class ShippingProviderResolver
{
    /** @var array<string, class-string<ShippingProviderInterface>> */
    protected const PROVIDERS = [
        'pathao' => PathaoProvider::class,
        'steadfast' => SteadfastProvider::class,
        'redx' => RedxProvider::class,
        'sundarban' => SundarbanProvider::class,
    ];

    public function resolve(string $provider): ShippingProviderInterface
    {
        $class = self::PROVIDERS[$provider] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unknown shipping provider [{$provider}].");
        }

        return app($class);
    }
}
