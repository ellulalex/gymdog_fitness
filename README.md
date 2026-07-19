# Gymdog Commerce Platform

Rebuild of [gymdog.fitness](https://gymdog.fitness) off WordPress/WooCommerce, structured so it can later be productised as a multi-tenant B2B commerce platform. See `docs/` for the full architecture spec and site audit.

**Stack:** Laravel 12 · Filament v4 (admin) · Blade + Livewire + Alpine (storefront) · Tailwind v4 · MySQL 8 · Stripe Connect (Phase 2).

## Phase status

- **Phase 0 — Foundations ✅** — tenancy layer, theme resolution, admin, CI, deploy script.
- **Phase 1 — Catalogue ✅** — products/variants/nested categories/brands, Filament admin, media library, storefront with faceted filtering.
- Phase 2 — Cart & checkout (Stripe Connect) ← the phase that matters
- Phase 3 — Content & CMS + WordPress migration
- Phase 4 — Content automation
- Phase 5 — Productisation (multi-tenant SaaS)

## Local setup

Requires PHP 8.4, Composer, Node 22, and MySQL (Herd bundles it). The site is served by Herd at `https://gymdog.test`.

```bash
composer install
npm install
cp .env.example .env          # defaults target the local MySQL "gymdog" db
php artisan key:generate
php artisan migrate --seed     # creates the gymdog tenant + admin user
npm run build                  # or: npm run dev
```

Admin: `https://gymdog.test/admin` — login with the seeded `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## Architecture notes

The platform runs **one tenant now, structured for many** (spec §4). Two things are load-bearing:

- **`App\Support\Tenancy`** — the `BelongsToTenant` trait adds a global scope and
  auto-fills `tenant_id` on every tenant-owned model. Apply it to every such
  model from its first migration. `TenantManager` holds the current tenant;
  `TenantResolver` picks it (by host, else the sole active tenant).
- **`App\Support\Theme`** — storefront views resolve theme-first
  (`resources/views/themes/{active}` → `themes/default` → app views). Gymdog is
  the first theme. Brand name and palette come from tenant settings, never
  literals.

Nothing hardcodes "gymdog" in the domain or template layers — it all lives in the
`tenants` table / `settings` JSON, seeded in `DatabaseSeeder`.

## Testing

```bash
./vendor/bin/pest
```

Tests run against in-memory SQLite. CI (`.github/workflows/ci.yml`) runs the
suite and an asset build on every push and PR.

## Deploy

`./deploy.sh [branch]` — builds assets locally (the server's npm times out),
rsyncs them, then pulls/installs/migrates/caches on the box. Fill in the
`DEPLOY_HOST` / `DEPLOY_PATH` config block once the Hetzner server is
provisioned; see the first-time-setup notes at the foot of the script.
