<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains every query on a tenant-owned model to the
 * current tenant. Applied automatically by the BelongsToTenant trait.
 *
 * If no tenant is resolved (e.g. a console command with no tenant context),
 * the scope is a no-op — callers in that situation see all rows and are
 * expected to know what they are doing.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(TenantManager::class);

        if (! $manager->has()) {
            return;
        }

        $builder->where(
            $model->getTable().'.'.$model->getTenantIdColumn(),
            $manager->id()
        );
    }
}
