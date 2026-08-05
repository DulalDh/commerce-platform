<?php

namespace App\Modules\Tenants\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registers Schema::enableTenantRLS('table') so later modules can enable
 * Postgres row-level security on a tenant-owned table in one line instead
 * of hand-writing the SQL each time.
 */
class RlsSchemaMacro
{
    public static function register(): void
    {
        Schema::macro('enableTenantRLS', function (string $table, string $column = 'tenant_id') {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            $policyName = "tenant_isolation_{$table}";

            DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$table}");
            // Postgres does NOT guarantee left-to-right short-circuit
            // evaluation of OR clauses - the planner can attempt the ::uuid
            // cast on 'bypass' or '' regardless of clause order, throwing
            // "invalid input syntax for type uuid". CASE/WHEN is the one
            // construct Postgres guarantees evaluates in order and stops at
            // the first matching branch, so it's used here instead of OR.
            // coalesce(...) treats a session var that was never set (NULL,
            // e.g. a fresh CLI/artisan connection) the same as an explicitly
            // empty one.
            DB::statement(<<<SQL
                CREATE POLICY {$policyName} ON {$table}
                USING (
                    CASE coalesce(current_setting('app.current_tenant_id', true), '')
                        WHEN 'bypass' THEN true
                        WHEN '' THEN {$column} IS NULL
                        ELSE {$column} = current_setting('app.current_tenant_id', true)::uuid
                    END
                )
            SQL);
        });

        Schema::macro('disableTenantRLS', function (string $table) {
            $policyName = "tenant_isolation_{$table}";
            DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        });
    }
}
