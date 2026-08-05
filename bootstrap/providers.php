<?php

use App\Modules\Auth\Providers\AuthModuleServiceProvider;
use App\Modules\Bookings\Providers\BookingsServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\CMS\Providers\CMSServiceProvider;
use App\Modules\Customers\Providers\CustomersServiceProvider;
use App\Modules\Inventory\Providers\InventoryServiceProvider;
use App\Modules\Marketplace\Providers\MarketplaceServiceProvider;
use App\Modules\Notifications\Providers\NotificationsServiceProvider;
use App\Modules\Orders\Providers\OrdersServiceProvider;
use App\Modules\Payments\Providers\PaymentsServiceProvider;
use App\Modules\Promotions\Providers\PromotionsServiceProvider;
use App\Modules\RBAC\Providers\RBACServiceProvider;
use App\Modules\Reports\Providers\ReportsServiceProvider;
use App\Modules\Reviews\Providers\ReviewsServiceProvider;
use App\Modules\Services\Providers\ServicesServiceProvider;
use App\Modules\Shipping\Providers\ShippingServiceProvider;
use App\Modules\Subscriptions\Providers\SubscriptionsServiceProvider;
use App\Modules\Tenants\Providers\TenantsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    TenantsServiceProvider::class,
    RBACServiceProvider::class,
    AuthModuleServiceProvider::class,
    CatalogServiceProvider::class,
    InventoryServiceProvider::class,
    OrdersServiceProvider::class,
    ServicesServiceProvider::class,
    BookingsServiceProvider::class,
    PaymentsServiceProvider::class,
    ShippingServiceProvider::class,
    MarketplaceServiceProvider::class,
    PromotionsServiceProvider::class,
    CustomersServiceProvider::class,
    NotificationsServiceProvider::class,
    ReportsServiceProvider::class,
    CMSServiceProvider::class,
    ReviewsServiceProvider::class,
    SubscriptionsServiceProvider::class,
];
