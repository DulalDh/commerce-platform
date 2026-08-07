<?php

use App\Models\User;
use App\Modules\RBAC\Models\Role;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Scopes\TenantScope;
use App\Modules\Tenants\Support\TenantContext;
use Illuminate\Support\Facades\DB;

function makeTenant(string $subdomain): Tenant
{
    return Tenant::create([
        'name' => "Tenant {$subdomain}",
        'type' => 'merchant',
        'status' => 'active',
        'subdomain' => $subdomain,
    ]);
}

function setSessionTenant(?string $tenantId): void
{
    DB::statement("select set_config('app.current_tenant_id', ?, false)", [$tenantId ?? '']);
}

beforeEach(function () {
    $this->tenantA = makeTenant('tenant-a');
    $this->tenantB = makeTenant('tenant-b');

    // Bypass RLS for fixture setup only - we're deliberately writing rows
    // across two tenants here, which the policy would otherwise (correctly)
    // refuse from any single tenant-scoped session.
    setSessionTenant('bypass');

    // Bypass Eloquent entirely for fixture setup so we don't accidentally
    // rely on app-layer scoping to produce correctly-isolated test data.
    DB::table('roles')->insert([
        'name' => 'staff', 'guard_name' => 'web',
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('roles')->insert([
        'name' => 'staff', 'guard_name' => 'web',
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);
});

test('eloquent global scope blocks cross-tenant reads', function () {
    app(TenantContext::class)->set($this->tenantA);
    setSessionTenant($this->tenantA->id);

    $roles = Role::all();

    expect($roles)->toHaveCount(1);
    expect($roles->first()->tenant_id)->toBe($this->tenantA->id);
});

test('RLS alone blocks cross-tenant reads when the global scope is bypassed', function () {
    app(TenantContext::class)->set($this->tenantA);
    setSessionTenant($this->tenantA->id);

    // Deliberately bypass the Eloquent-level filter - if this still only
    // returns Tenant A's row, the database itself is enforcing isolation.
    $roles = Role::withoutGlobalScope(TenantScope::class)->get();

    expect($roles)->toHaveCount(1);
    expect($roles->first()->tenant_id)->toBe($this->tenantA->id);
});

test('global scope alone blocks cross-tenant reads when RLS is bypassed at the DB layer', function () {
    app(TenantContext::class)->set($this->tenantA);
    // Simulate an RLS misconfiguration: put the DB layer into bypass mode so
    // it would allow every row if it were the only line of defense. The
    // Eloquent scope must still filter correctly on its own.
    setSessionTenant('bypass');

    $roles = Role::all();

    expect($roles)->toHaveCount(1);
    expect($roles->first()->tenant_id)->toBe($this->tenantA->id);
});

test('a superuser connection bypasses RLS, proving the app must never run as one', function () {
    // Laravel wraps the default ('pgsql') connection's writes in a test
    // transaction that other connections can't see, so this negative
    // control writes and reads entirely on the superuser connection itself
    // and cleans up after - it isn't testing app behavior, just documenting
    // that RLS provides no protection against a superuser session.
    $migrate = DB::connection('pgsql_migrate');

    $roleId = $migrate->table('roles')->insertGetId([
        'name' => 'superuser-probe', 'guard_name' => 'web',
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    setSessionTenant($this->tenantA->id);

    $roles = $migrate->table('roles')->where('id', $roleId)->get();

    $migrate->table('roles')->where('id', $roleId)->delete();

    expect($roles)->toHaveCount(1);
});

test('super-admin sees roles across all tenants, tenant owner is forbidden from the endpoint', function () {
    $ownerRole = Role::withoutGlobalScopes()->create([
        'name' => 'tenant-owner', 'guard_name' => 'web', 'tenant_id' => $this->tenantA->id,
    ]);

    $owner = User::create([
        'tenant_id' => $this->tenantA->id,
        'name' => 'Owner A',
        'email' => 'owner-a@example.test',
        'password' => bcrypt('password'),
    ]);
    $owner->assignRole($ownerRole);

    $superAdminRole = Role::withoutGlobalScopes()->firstOrCreate([
        'name' => 'super-admin', 'guard_name' => 'web', 'tenant_id' => null,
    ]);
    $admin = User::create([
        'tenant_id' => null,
        'name' => 'Super Admin',
        'email' => 'admin@example.test',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole($superAdminRole);

    // A tenant-scoped actor has no business on the platform-wide RBAC
    // management endpoint at all.
    $this->actingAs($owner, 'sanctum')
        ->getJson('/api/v1/roles')
        ->assertForbidden();

    // Super Admin explicitly bypasses tenant scoping to manage RBAC across
    // every tenant - both tenants' roles must be visible here.
    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonFragment(['tenant_id' => $this->tenantA->id])
        ->assertJsonFragment(['tenant_id' => $this->tenantB->id]);
});

test('a plain customer with no tenant_id and no role is never granted the super-admin RLS/scope bypass', function () {
    // Regression for a bug where ResolveTenant::isSuperAdmin() checked
    // tenant_id === null, which is also true of every ordinary
    // self-registered customer (register() never sets tenant_id) - meaning
    // any such customer was silently granted a full tenant-scoping bypass.
    $customer = User::create([
        'tenant_id' => null,
        'name' => 'Plain Customer',
        'email' => 'plain-customer@example.test',
        'password' => bcrypt('password'),
    ]);

    // No X-Tenant-ID header, no role: must resolve to "no tenant, no
    // bypass" and see nothing on a tenant-scoped endpoint - not everything.
    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a plain customer cannot use X-Tenant-ID to impersonate a merchant tenant', function () {
    $victimOwner = User::create([
        'tenant_id' => $this->tenantA->id,
        'name' => 'Victim Owner',
        'email' => 'victim-owner@example.test',
        'password' => bcrypt('password'),
    ]);

    $attacker = User::create([
        'tenant_id' => null,
        'name' => 'Attacker',
        'email' => 'attacker@example.test',
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($attacker, 'sanctum')
        ->withHeaders(['X-Tenant-ID' => $this->tenantA->id])
        ->getJson('/api/v1/customers');

    $response->assertOk()->assertJsonCount(0, 'data');
});
