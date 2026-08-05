<?php

namespace App\Modules\Tenants\Support;

use App\Modules\Tenants\Models\Tenant;

/**
 * Request-scoped source of truth for "what tenant is this request acting as."
 * Both TenantScope (Eloquent) and ResolveTenant middleware (RLS session var)
 * read from this single instance so the two isolation layers never drift apart.
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected bool $bypass = false;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->bypass = false;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->bypass = false;
    }

    /**
     * Explicitly bypass tenant scoping (Super Admin / console / queue contexts).
     * Must be set deliberately, never as a default.
     */
    public function bypass(): void
    {
        $this->bypass = true;
    }

    public function isBypassing(): bool
    {
        return $this->bypass;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?string
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
