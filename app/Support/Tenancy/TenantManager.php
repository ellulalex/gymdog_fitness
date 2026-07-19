<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

/**
 * Holds the tenant for the current request/console context.
 *
 * Phase 0 runs a single tenant (gymdog), so this almost always resolves to the
 * same row — but every query and every insert already flows through it, so
 * switching on real multi-tenancy later is a config change, not a schema audit.
 *
 * Bound as a singleton (alias "tenant") in TenancyServiceProvider.
 */
class TenantManager
{
    protected ?Tenant $tenant = null;

    protected bool $resolved = false;

    public function __construct(protected TenantResolver $resolver) {}

    /** The active tenant, resolved lazily on first access. */
    public function current(): ?Tenant
    {
        if (! $this->resolved) {
            $this->tenant = $this->resolver->resolve();
            $this->resolved = true;
        }

        return $this->tenant;
    }

    /** Force a specific tenant (used in tests and, later, admin impersonation). */
    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;
    }

    /** Forget the resolved tenant so the next access re-resolves. */
    public function forget(): void
    {
        $this->tenant = null;
        $this->resolved = false;
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }

    public function has(): bool
    {
        return $this->current() !== null;
    }
}
