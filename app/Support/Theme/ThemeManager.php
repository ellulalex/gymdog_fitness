<?php

namespace App\Support\Theme;

use App\Support\Tenancy\TenantManager;

/**
 * Resolves which storefront theme is active and where its views live.
 *
 * The active theme comes from the current tenant's `theme` setting, falling
 * back to the configured default. Views resolve theme-first (see
 * ThemeServiceProvider), so a tenant theme overrides only what it needs and
 * inherits everything else from the fallback theme.
 */
class ThemeManager
{
    public function __construct(protected TenantManager $tenants) {}

    public function active(): string
    {
        return $this->tenants->current()?->setting('theme')
            ?? config('themes.default');
    }

    public function fallback(): string
    {
        return config('themes.fallback');
    }

    public function path(string $theme): string
    {
        return rtrim(config('themes.path'), '/')."/{$theme}";
    }
}
