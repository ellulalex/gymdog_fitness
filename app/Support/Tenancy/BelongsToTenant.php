<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every tenant-owned model. It does two things, both from day one:
 *
 *  1. Adds a global scope so reads are automatically constrained to the
 *     current tenant (see TenantScope).
 *  2. Auto-fills `tenant_id` on create from the current tenant, so application
 *     code never has to remember to set it.
 *
 * Doing this now — while there is only one tenant — is deliberate. Retrofitting
 * tenant filtering onto a live schema later turns every existing query into a
 * potential data-leak audit. See the platform spec, §4.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $column = $model->getTenantIdColumn();

            if (empty($model->{$column})) {
                $manager = app(TenantManager::class);

                if ($manager->has()) {
                    $model->{$column} = $manager->id();
                }
            }
        });
    }

    public function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Escape hatch: query across all tenants, bypassing the global scope. */
    public static function withoutTenantScope(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }
}
