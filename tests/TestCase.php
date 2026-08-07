<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests build fixtures directly via Eloquent/factories, outside any HTTP
     * request - so ResolveTenant never runs and the RLS session var is never
     * set. Default every test's connection to RLS-bypass (mirroring queue
     * workers and console commands) so ordinary fixture setup isn't fighting
     * row-level security by default. Tests that specifically want to
     * exercise tenant isolation (e.g. TenantIsolationTest) explicitly manage
     * the session var themselves and this default has no effect on them.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement("select set_config('app.current_tenant_id', 'bypass', false)");
    }
}
