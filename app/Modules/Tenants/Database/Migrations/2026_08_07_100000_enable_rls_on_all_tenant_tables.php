<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every tenant-owned table beyond `roles` (the original RLS pilot) was
     * left protected by the Eloquent global scope alone - an app-layer-only
     * control that a raw query, a missing scope, or (as found in the Phase 5
     * security audit) a tenant-context resolution bug can silently bypass.
     * This closes that gap by giving every one of them the same
     * database-enforced last line of defense `roles` already had.
     */
    protected array $tables = [
        'products', 'brands', 'categories', 'tags', 'product_images', 'product_variants',
        'inventory_items', 'stock_movements',
        'carts', 'cart_items', 'orders', 'order_items', 'order_status_histories',
        'payments',
        'customer_notes', 'customer_lifetime_values',
        'commissions', 'subscriptions', 'subscription_invoices',
        'coupons', 'flash_sales', 'promotion_rules',
        'shipments',
        'services', 'service_categories', 'provider_profiles', 'staff', 'staff_leaves', 'vehicles',
        'bookings', 'booking_status_histories', 'holidays',
        'reviews',
        'sales_reports', 'booking_reports', 'provider_performance_reports', 'top_selling_product_reports',
        'pages', 'blog_posts', 'faqs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::enableTenantRLS($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::disableTenantRLS($table);
        }
    }
};
