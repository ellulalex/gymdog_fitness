<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Decides which tenant a request belongs to.
 *
 * Phase 0 strategy (single tenant): match the request host against
 * `tenants.domain`; if nothing matches (or we're on the CLI), fall back to the
 * sole active tenant. Domain-based routing for many tenants is deferred to
 * Phase 5 — this class is the one place that changes when it arrives.
 */
class TenantResolver
{
    public function __construct(protected Request $request) {}

    public function resolve(): ?Tenant
    {
        // During early migrations the table may not exist yet.
        try {
            if (! Schema::hasTable('tenants')) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        $host = $this->request->getHost();

        if ($host) {
            $byDomain = Tenant::query()
                ->where('status', 'active')
                ->where('domain', $host)
                ->first();

            if ($byDomain) {
                return $byDomain;
            }
        }

        return Tenant::query()->where('status', 'active')->first();
    }
}
