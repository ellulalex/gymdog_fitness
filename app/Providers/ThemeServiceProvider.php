<?php

namespace App\Providers;

use App\Support\Tenancy\TenantManager;
use App\Support\Theme\ThemeManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
    }

    public function boot(): void
    {
        $theme = $this->app->make(ThemeManager::class);

        $finder = View::getFinder();

        // Register lowest priority first: fallback theme, then the active theme
        // on top of it. prependLocation() pushes to the front, so the active
        // theme wins, then the fallback theme, then the app's own views.
        foreach ([$theme->fallback(), $theme->active()] as $name) {
            $path = $theme->path($name);

            if (File::isDirectory($path)) {
                $finder->prependLocation($path);
            }
        }

        // Make the current tenant available to every storefront view so
        // templates read brand, palette and settings from data, never literals.
        View::composer('*', function ($view) {
            $view->with('tenant', $this->app->make(TenantManager::class)->current());
        });
    }
}
