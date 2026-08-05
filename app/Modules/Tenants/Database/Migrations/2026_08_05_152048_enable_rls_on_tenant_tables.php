<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // users is deliberately excluded: Super Admins have no tenant, and
        // authentication must look a user up by email before any tenant
        // context exists, which RLS on this table would make impossible.
        // Tenant scoping for users is enforced at the app layer only.
        Schema::enableTenantRLS('roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableTenantRLS('roles');
    }
};
