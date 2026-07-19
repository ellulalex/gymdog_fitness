<?php

namespace App\Providers;

use App\Support\Tenancy\TenantManager;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager($app->make(TenantResolver::class));
        });

        $this->app->alias(TenantManager::class, 'tenant');
    }
}
