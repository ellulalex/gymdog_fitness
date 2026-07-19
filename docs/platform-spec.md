# Gymdog Commerce Platform — Architecture & Build Plan

**Status:** Draft v2 — July 2026 *(revised after site crawl)*
**Owner:** Alex
**Purpose:** Technical spec for rebuilding gymdog.fitness off WordPress, structured so it can later be productised as a multi-tenant B2B commerce platform.
**Companion document:** `gymdog-site-audit.md` — full URL inventory, current stack, and UI component list.

---

## 1. Context

gymdog.fitness is a CrossFit equipment and apparel shop serving Malta, currently on WordPress/WooCommerce, with some fitness-related editorial content.

Two goals, in priority order:

1. **Near term:** Replace WordPress with a shop Alex can manage himself and that reliably takes money.
2. **Longer term:** Productise the platform and sell it B2B to other merchants who want a better experience and cheaper pricing than Shopify.

The second goal does not mean building multi-tenancy now. It means **not making decisions now that make multi-tenancy expensive later**. Section 4 covers exactly which decisions those are.

**Confirmed scale (18 July 2026 crawl):** 17 products, 7 product categories across 2 levels, 4 brands, 18 pages, 10 blog posts — roughly 46 indexed URLs. Small enough that migration is days, not weeks.

> **Status note:** the site was down on 18 July (database error, then a Cloudflare 526 after server rebuild). Now serving on Cloudflare SSL mode "Full" as a temporary measure — an Origin Certificate still needs installing so it can return to Full (strict).

---

## 2. Guiding principles

- **Ship revenue first.** The shop taking payments is the only thing that matters in phase 1. CMS and content automation are worth zero until then.
- **Visual parity with the current site.** The rebuild should look like today's site, not a redesign. Redesigning and re-platforming simultaneously means you can't tell which change caused a drop in conversion. See §4a.
- **Tenant-ready, not tenant-aware.** Structure for many merchants; run one.
- **Boring where it's risky.** Payments, tax, and stock are where bugs cost real money. Use battle-tested libraries; save the creativity for the storefront.
- **The storefront is the product.** Filament gives you the admin for free. Differentiation lives in the customer-facing experience.
- **Every phase is deployable.** No phase ends with a half-migrated site.

---

## 3. Stack

| Layer | Choice | Rationale |
|---|---|---|
| Framework | Laravel 12 | Already known; existing Hetzner + Herd + deploy pipeline |
| Admin | Filament v4 | MIT, free, generates all management UI |
| Storefront | Blade + Livewire + Alpine | Same stack as Filament, no separate SPA to maintain |
| CSS | Tailwind | Filament dependency anyway |
| DB | MySQL 8 (or Postgres 16) | Either is fine; MySQL if matching the existing Photocart box |
| Payments | Stripe Connect | See §7 |
| Search | DB `LIKE` → Meilisearch later | Don't add infra before you need it |
| Queue | Redis + Horizon | Needed for the content worker and webhooks |
| Media | Spatie Media Library → S3-compatible storage | Hetzner Object Storage or Cloudflare R2 |
| Deploy | Existing rsync + git pull script | Already proven on myshot.gymdog.fitness |

**Deliberately rejected:** off-the-shelf Laravel e-commerce packages (Lunar, Bagisto). They accelerate phase 1 but you inherit their data model — which is the exact thing you need to own if the platform is the product.

---

## 4. Tenancy strategy

**Model:** single database, `tenant_id` foreign key on every tenant-owned table. Not separate databases, not separate schemas.

**Now:** one tenant row (gymdog), seeded in the initial migration. `tenant_id` is populated everywhere but never varies.

**The four things that must be right from commit one:**

1. **`tenant_id` on every tenant-owned table**, nullable, from the very first migration. Backfilling this across a live schema later is the single most painful retrofit in this project.

2. **A global scope applied by default.** Write a `BelongsToTenant` trait that adds a global scope filtering on the current tenant and auto-fills `tenant_id` on create. Apply it now, even with one tenant. If tenant filtering is added later, every existing query becomes a potential data-leak audit.

3. **No hardcoded gymdog anywhere in the domain layer.** Store name, logo, colours, currency, VAT number, contact details, social links, shipping origin — all in the `tenants` table or a `tenant_settings` JSON column. Templates read from config, never literals.

4. **Theme layer separated from application layer.** Storefront views live in a themable directory resolved per tenant, with gymdog as the first theme. Views resolve theme-first, falling back to a default.

**Explicitly deferred:** tenant signup flow, domain routing, per-tenant billing, plan limits, tenant-scoped file storage paths, admin impersonation. All of these are cheap to add later *if* the four items above are done now.

---

## 4a. UI parity

The current site runs the **Zank** theme (Ninetheme) with Elementor. The rebuild reimplements that design in Blade/Tailwind — same layout, same palette, same components — without the Elementor dependency.

**Palette:** white background · dark forest green (footer, badges, active states) · warm tan/beige (CTAs, promo banners) · muted pink/red (sale and out-of-stock badges). Geometric rounded sans throughout, large light-weight headings, generous whitespace.

**Components to reimplement** (full detail in the audit document):

- Header — centred logo, left nav, right icon cluster with cart/wishlist count badges, sticky
- Homepage — hero slider · intro block · article carousel · promo banner with discount code chip · product carousel · four trust badges · footer
- Shop — sidebar filters (price slider, colour swatches with counts, nested category tree, brand) · toolbar (per-page, grid density, sorting) · product tiles with sale/discount/out-of-stock badges
- Product — thumbnail rail + gallery · colour swatches · size buttons · quantity stepper · add to cart · wishlist · buy now · express payment button · Delivery & Return and Size Guide accordions · SKU/category/brand meta · social share

**Deliberate fixes while rebuilding** — these are theme quirks, not design decisions worth preserving:

- Duplicate search icon in the header (currently two)
- Main product image blank above the fold until scroll (lazy-load misfire)
- Hardcoded promo copy — should be a scheduled banner with a validity window
- Empty SKUs

**Build the theme layer properly** (per §4, item 4). Gymdog's look becomes the first theme; a second merchant gets a second theme. This is the one place where "do it right now" pays for itself immediately, since you're building the theme system anyway.

---

## 5. Data model

Core tables. `tenant_id` is implied on every table marked †.

### Catalogue

```
products †
  id, tenant_id, slug, name, description, status (draft|active|archived),
  brand_id, tax_class, meta_title, meta_description, published_at, timestamps

product_variants †
  id, product_id, sku, name, price_cents, compare_at_price_cents,
  stock_qty, weight_grams, barcode, position, timestamps
  -- every product has ≥1 variant, even simple ones. Uniform handling
  -- avoids the "simple vs variable product" branching that plagues Woo.

variant_options / variant_option_values
  -- size, colour etc. Many-to-many onto variants.

categories †        -- nested set or adjacency list
category_product    -- pivot
brands †
```

### Orders

```
orders †
  id, tenant_id, number, customer_id (nullable — guest checkout),
  email, status, payment_status, fulfilment_status,
  currency, subtotal_cents, discount_cents, shipping_cents,
  tax_cents, total_cents,
  billing_address (json), shipping_address (json),
  stripe_payment_intent_id, placed_at, timestamps

order_lines †
  id, order_id, variant_id (nullable — variant may be deleted),
  name_snapshot, sku_snapshot, unit_price_cents, qty,
  tax_rate, tax_cents, total_cents
  -- snapshot everything. An order must render correctly
  -- forever, even if the product is deleted.

customers †
  id, tenant_id, user_id (nullable), email, name, phone,
  accepts_marketing, timestamps

addresses †
carts †             -- session or customer keyed, with expiry
cart_lines †
discounts †         -- code, type, value, usage limits, validity window
```

### Content

```
pages †             -- slug, title, blocks (json), status, seo fields
posts †             -- slug, title, excerpt, body, author_id, status,
                       published_at, source (human|generated),
                       generation_meta (json), seo fields
post_categories †
media               -- via Spatie Media Library
redirects †         -- from_path, to_path, status_code
                       (critical for the WordPress migration — see §10)
```

### Platform

```
tenants
  id, slug, name, domain, settings (json),
  stripe_account_id, plan, status, timestamps

users               -- staff logins; pivot to tenants for future multi-tenant staff
```

### Money and dates

- **All money in integer cents.** No floats, no decimals. Ever.
- Store currency alongside every monetary value even while EUR-only.
- All timestamps UTC; render in tenant timezone.

---

## 6. Application structure

```
app/
  Domain/                 # framework-light business logic
    Catalogue/
    Orders/               # OrderBuilder, state machine, totals calculator
    Cart/
    Payments/
    Tax/
    Content/
  Filament/
    Resources/            # admin CRUD
    Widgets/
  Http/
    Controllers/Storefront/
    Livewire/             # cart, checkout, search
  Jobs/
    Content/              # generation pipeline
  Support/
    Tenancy/              # BelongsToTenant trait, resolver, global scope

resources/
  views/
    themes/
      default/            # fallback theme
      gymdog/             # tenant theme, overrides default
    filament/
```

**Rule:** `Domain/` must not know about Filament, HTTP, or Livewire. This is what makes the logic reusable when the platform gets a second face (API, mobile, headless).

**Order totals must be one calculator class**, called identically from cart preview, checkout, admin order creation, and tests. Divergent totals logic is the most common source of "the customer was charged the wrong amount" bugs.

---

## 7. Payments — Stripe Connect

### Why Connect, not plain Stripe

Plain Stripe means money lands in Alex's account. The moment a second merchant exists, that model breaks and you'd be rewriting the payments layer — and potentially holding other people's funds, which carries licensing implications you don't want.

Connect solves this: each tenant gets a connected account, funds settle to them directly, and you take an application fee.

### Recommended configuration

- **Account type:** Connect with Stripe-hosted onboarding. Merchant completes KYC through Stripe's flow — you never handle their identity documents.
- **Charge type:** **Destination charges** with `application_fee_amount`. The platform is the merchant of record for the payment, funds route to the connected account, your fee is deducted automatically.
- **Fee handling:** let Stripe set payment pricing and bill connected accounts directly. Per Stripe's current documentation this means the platform incurs no separate Connect account, payout, or tax-reporting fees, and may qualify for revenue share.
- **Phase 1:** create a connected account for gymdog itself. Same code path as every future tenant — you dogfood the onboarding flow before selling it.

### Baseline pricing (verify at build time)

Roughly 2.9% + fixed fee per transaction; international cards add ~1%, currency conversion ~1%; payouts carry a 0.25% fee capped per payout. Confirm current EU/Malta-specific rates against Stripe's pricing page before modelling margins.

### Implementation notes

- Use Stripe **Payment Intents** with **Stripe Elements** or Checkout. Never touch raw card data — PCI scope stays minimal.
- **Webhooks are the source of truth for payment state,** not the browser redirect. Users close tabs. Handle `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`, `account.updated`.
- **Idempotency keys on every write to Stripe.** Retries will happen.
- **Never mark an order paid from client-side callback.** Only webhooks flip payment status.
- Store the Stripe event ID for every processed webhook and reject duplicates — Stripe delivers at-least-once.

---

## 8. Tax

Malta VAT is 18%. The relevant rules:

- **Domestic Maltese sales:** 18% VAT.
- **Cross-border B2C within the EU:** the EU-wide distance-selling threshold is **€10,000/year across all member states combined**. Below it you may charge your domestic rate. Above it you must charge the **customer's** member state rate and report via **OSS** — a single quarterly return filed in Malta covering all 27 states.
- The old per-country thresholds (Malta's was €35,000) were abolished in July 2021.

**Design implication:** even if gymdog is under the threshold today, build the tax layer as *"resolve rate from destination country + product tax class,"* not as a hardcoded 18%. A rate table keyed by country and tax class costs almost nothing now and saves a painful refactor when either gymdog crosses €10k in EU sales or a B2B customer in another member state signs up.

Prices display **VAT-inclusive** — standard EU B2C expectation and a legal requirement for consumer-facing prices.

Confirm current obligations with an accountant before launch. This spec is not tax advice.

---

## 9. Content worker

### Recommended design: generate → queue → auto-publish with a brake

You indicated a preference for fully automatic publishing. The honest concern: Google's spam policies explicitly target scaled content abuse, and an unsupervised publishing pipeline is the pattern those policies describe. The risk isn't one weak post — it's a manual action against the domain the whole business runs on.

**Suggested compromise that preserves the automation:**

```
Scheduled job (weekly)
  → topic selection from a curated queue
  → generation with tenant-specific brand voice + product context
  → automated quality gates
  → status = 'scheduled', publish_at = now + 24h
  → auto-publishes unless rejected
  → Slack/email notification with one-click reject
```

Zero routine effort — you only act to *stop* something. But you keep a hand on the brake, and you have an audit trail if you ever need to demonstrate editorial oversight.

**Quality gates before scheduling:**

- Minimum word count and structural checks
- Duplicate/near-duplicate detection against existing posts
- Banned-claims filter — **critical for a fitness site.** No medical claims, no dosage advice, no injury-treatment guidance, no supplement health claims. This is regulatory exposure, not just quality control.
- Internal link validity, product references resolve to real in-stock products
- Factual claims flagged for review rather than published silently

**Tag every generated post** with `source = 'generated'` and full `generation_meta` (model, prompt version, timestamp, topic source). Non-negotiable for auditing and for retroactively pulling content if quality drifts.

**Make it tenant-aware from the start** if content generation is part of the B2B pitch — brand voice, product catalogue, and topic queues all become per-tenant config.

**Recommendation:** run phase 4 in human-approval mode for the first ~20 posts. Measure whether they rank and read well. Loosen to auto-publish once the output has earned it.

---

## 10. WordPress migration

Products are being recreated manually, so migration scope is content only: **18 pages and 10 posts.** Realistically 2–3 days.

**The good news from the crawl:** the current URL patterns are all cleanly reproducible, so the redirects table stays nearly empty. Keep these exactly:

| Content type | Pattern |
|---|---|
| Products | `/product/{slug}/` |
| Product categories | `/shop/{cat}/{subcat}/` |
| Brands | `/product-brands/{slug}/` |
| Posts | `/{slug}/` — **root level, not under `/blog/`** |
| Post categories | `/category/{slug}/` |
| Pages | `/{slug}/` |

Only two redirects needed: `/about-me/` → `/about-us/` (Ninetheme demo leftover) and `/category/uncategorized/` → `/blog/`.

Note that **eight "pages" are actually CrossFit movement guides** (`/the-power-clean/`, `/thruster/`, `/9-foundational-movements/` etc.) sitting under the CROSSFIT nav dropdown. Model these as posts or a dedicated guides type, but keep the root-level URLs.

Process:

1. Export the full URL list — sitemap, Search Console, and a crawl of the live site (once the database is back up).
2. Export posts and pages via the WordPress REST API or WXR export.
3. Convert to the new content model; download and re-host media locally.
4. Build the `redirects` table mapping every old path to its new location.
5. Verify with a crawl post-launch: zero unintended 404s on previously indexed URLs.
6. Keep the WordPress database backed up and archived — do not decommission until the new site has been stable and indexed for at least a month.

---

## 11. Phased plan

Each phase ends deployable.

### Phase 0 — Foundations *(~1 week)*
Fix the live database error. New Laravel project, Filament installed, `tenants` table with gymdog seeded, `BelongsToTenant` trait and global scope, theme resolution layer, CI running tests, deploy script adapted from the Photocart pipeline.

**Exit:** empty themed site deploys to a staging domain; admin login works.

### Phase 1 — Catalogue *(~2–3 weeks)*
Products, variants (colour + size — confirmed needed by the Velites grips), **nested** categories, brands. Filament resources for all of it. Media library. Storefront product listing, category pages, product detail. Sidebar filtering by price, colour, category, and brand with result counts. Grid density and sort controls. Basic search.

*Revised up from 2 weeks: the crawl showed the filtering is richer than assumed. Filtering products by variant-level attributes (colour) with accurate counts is a specific query design, not a checkbox.*

**Exit:** Alex can add a real product with variants in admin, and it appears correctly on the storefront with working filters.

### Phase 2 — Cart & checkout *(~3 weeks)* ← **the phase that matters**
Cart (Livewire, guest + logged-in), destination-based tax resolution, **free-shipping-over-€50 Malta rule**, discount codes (an active `GYMDOG5` code exists today), Stripe Connect onboarding for gymdog, Payment Intents checkout, express wallet buttons (Google Pay / Apple Pay), webhook handling, order confirmation emails, order management in Filament, refunds.

*Before starting: identify which gateway currently powers the live Google Pay button. If it's already Stripe, migrating that account into Connect is a different job from starting fresh.*

**Exit:** a real customer completes a real purchase with a real card, and the order appears correctly in admin.

### Phase 3 — Content & CMS *(~1 week)*
Pages with a block editor, posts, categories, SEO fields, sitemap generation, redirects table and middleware, content migration (18 pages + 10 posts), wishlist, DNS cutover.

*Revised down from 2 weeks — the crawl confirmed only 28 pieces of content.*

**Exit:** gymdog.fitness serves entirely from the new platform; no unintended 404s on previously indexed URLs.

### Phase 4 — Content automation *(~2 weeks)*
Topic queue, generation pipeline, quality gates, scheduling, review/reject notifications, generated-content tagging.

**Exit:** posts generate and publish on schedule with the review brake in place.

### Phase 5 — Productisation *(scope after phases 0–4 prove out)*
Tenant signup, domain routing, tenant-scoped storage, plan limits and platform billing, theme customisation UI, onboarding flow, merchant documentation.

**Do not start phase 5 until gymdog has been running on the platform for a meaningful period and taking real orders.** Customer zero has to be genuinely happy before customer one is worth pursuing.

*Estimates assume part-time solo work and are deliberately rough.*

---

## 12. Non-goals

Explicitly out of scope, to prevent scope creep:

- Multi-currency (EUR only until a customer needs otherwise)
- Multi-language (English only initially)
- Subscriptions or recurring products
- Marketplace/multi-vendor within a single tenant
- POS or physical retail integration
- Mobile apps
- Anything justified as "we'll need it when we have customers" before there are customers

---

## 13. Open questions

*Resolved by the crawl: content volume (28 items), catalogue size (17 products), category depth (2 levels), and the partial shipping picture (free over €50, Malta only).*

Still open:

1. **Which payment gateway is live today?** A Google Pay button is active on product pages, so something is integrated. If it's Stripe, that changes the Connect migration path.
2. **Shipping beyond the €50 rule.** What's charged below €50? Which carrier? Is there pickup, and does anything ship outside Malta? Affects phase 2 scope.
3. **Stock reality.** One product currently shows out-of-stock, so stock is tracked at least somewhat. Is it accurate, or does it drift?
4. **Wishlist — keep or drop?** It exists with a header count badge. Rebuilding costs time; dropping is fine if nobody uses it. Check analytics if available.
5. **Existing order history.** Not migrating — but confirm there's no legal retention obligation. Archive the WordPress database regardless.
6. **B2B target profile.** Which merchants — Maltese SMEs generally, fitness/sports niche, or something else? Should shape phase 5, and arguably phases 1–4.
7. **Pricing model for the eventual product.** Flat monthly, transaction percentage, or hybrid? Determines whether Stripe application fees or separate subscription billing is the mechanism.
8. **Who else touches the admin?** Determines how much role/permission work is needed. One post is authored by "admin" and others by "Alex Ellul", suggesting at least two accounts.

---

## 14. Immediate next steps

1. ~~Restore the live site~~ — done. Now install a Cloudflare Origin Certificate and move SSL back to Full (strict).
2. Check the blank-product-image issue on shop and product pages — it's costing conversions today, independent of the rebuild.
3. Identify the current payment gateway (§13, Q1).
4. Export page and post content from WordPress via the REST API while the site is up and healthy.
5. Answer the remaining open questions in §13, particularly shipping rates below €50.
6. Stand up phase 0 and get the deploy pipeline working end to end.

---

## Sources

- [Stripe Connect pricing](https://stripe.com/connect/pricing)
- [Stripe Connect documentation](https://docs.stripe.com/connect)
- [Build a SaaS platform with Connect](https://docs.stripe.com/connect/saas)
- [How to accept payments in Malta](https://stripe.com/resources/more/payments-in-malta)
- [EU VAT One Stop Shop — European Commission](https://vat-one-stop-shop.ec.europa.eu/index_en)
- [VAT OSS threshold explained](https://amavat.eu/vat-oss-threshold-explained-what-happens-after-e10000/)
- [Malta VAT rates and compliance](https://www.numeral.com/blog/malta-vat-rates-and-compliance)
- [Filament](https://filamentphp.com/)
