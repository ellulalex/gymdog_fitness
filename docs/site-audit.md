# gymdog.fitness — Site Audit

**Crawled:** 18 July 2026
**Source:** All in One SEO sitemaps + page inspection
**Purpose:** Complete inventory of what exists today, so the rebuild scope is based on facts rather than estimates.

---

## Summary

The site is much smaller than a "WordPress rewrite" typically implies. **46 URLs total.** That's a weekend of migration work, not a fortnight.

| Type | Count |
|---|---|
| Products | 17 |
| Product categories | 7 (2 nested) |
| Brands | 4 |
| Pages | 18 |
| Blog posts | 10 (+ blog index) |
| Post categories | 2 |
| **Total indexed URLs** | **~46** |

---

## Current stack

| Component | Detected |
|---|---|
| CMS | WordPress + WooCommerce |
| Theme | **Zank** by Ninetheme (`ninetheme.com/themes/zank2`) |
| Page builder | Elementor |
| SEO | All in One SEO v4.4.7.1 |
| Forms | WPForms Lite |
| Email | MailPoet |
| Brands | Custom taxonomy `zank_product_brands` (theme-provided) |
| Wishlist | Theme-provided |
| CDN/DNS | Cloudflare |

**Note:** a **Google Pay** button appears on product pages, so a card gateway is already integrated — likely Stripe. Worth confirming which, as it affects whether you're migrating an existing Stripe account into Connect or starting fresh.

---

## URL inventory

### Products — `/product/{slug}/` (17)

| Slug | Notes |
|---|---|
| `velites-hand-grips-all-terrain` | €54.95, colour + size variants |
| `hand-grips-quad-competition` | |
| `velites-lifting-belt` | |
| `jump-rope-fire-2-0` | |
| `venta-x2-knee-sleeves-5mm` | |
| `speed-skipping-rope` | €9.99 |
| `lumbar-belt` | €34.95 — **currently out of stock** |
| `jump-rope-abs-b` | €14.50 |
| `elastic-wrist-strap-for-weightlifting-powerlifting` | |
| `condor-grips` | |
| `callus-performance-callus-remover` | |
| `bumblebee-x2-gymnastic-grips-3-hole` | |
| `merlin-x4-gymnastic-grips-fingerless` | |
| `merlin-x3-gymnastic-grips-fingerless` | |
| `panda-x3-gymnastic-grips-3-hole` | |
| `reyllen-gx-belt` | |
| `hex-tech-knee-pads-5mm-0-2` | €44.95 |

Price range observed: **€9.99 – €54.95**. At least one product carries a SALE badge with a percentage discount.

### Product categories — `/shop/{slug}/` (7)

```
grips
  └─ fingerless
belts
knee-sleeves
accessories
  └─ wrist-straps
jump-ropes
```

**Two levels of nesting confirmed** — the category model needs to support hierarchy.

### Brands — `/product-brands/{slug}/` (4)

`reyllen` · `picsil` · `domyos` · `velites`

### Pages (18)

**Commerce/system:** `/` · `/shop/` · `/wishlist/` · `/contact/` · `/faq/` · `/about-us/` · `/about-me/` · `/events/` · `/terms-conditions/` · `/privacy-policy/`

**CrossFit content (movement/technique guides):** `/the-power-clean/` · `/weightlifting-in-crossfit/` · `/9-foundational-movements/` · `/thruster/` · `/gymnastics-in-crossfit/` · `/cardiovascular-fitness/` · `/the-devils-press/` · `/crossfit-boxes-in-malta/`

Note that eight of these are editorial content stored as **pages**, not posts — presumably to sit under the CROSSFIT nav dropdown. In the new model these are better as posts (or a dedicated "guides" content type) with the URLs preserved via redirects.

`/about-us/` and `/about-me/` both exist. `/about-me/` still references Ninetheme demo images — it's almost certainly leftover theme demo content and a candidate for deletion rather than migration.

### Blog posts — root-level slugs (10)

`meet-the-full-2025-crossfit-games-roster` · `how-to-watch-the-2025-crossfit-games` · `jack-monaghan-hit-with-4-year-crossfit-ban` · `getting-started-in-crossfit` · `are-crossfit-and-hyrox-the-same` · `why-train-the-core-and-not-just-abs` · `why-training-is-for-development` · `the-crossfit-open` · `cultivating-wellness-the-crucial-role-of-nutrition-for-adults` · `the-vital-role-of-early-nutrition-education`

Plus `/blog/` index and categories `/category/crossfit/`, `/category/uncategorized/`.

**Posts sit at the site root** (`/slug/`), not under `/blog/slug/`. The new routing must match this, or every post needs a redirect.

**Publishing cadence:** 7 posts in Oct–Nov 2023, then nothing until 3 posts in Jul–Aug 2025. Roughly 10 posts in three years. Relevant context for the content automation phase — the bar for "more than the status quo" is very low.

---

## UI inventory — for design parity

You want to keep the current look. Here's the component inventory to rebuild against.

### Visual language

- **Background:** white / very light neutral
- **Primary:** dark forest green (footer, badges, size buttons)
- **Secondary:** warm tan/beige (CTA buttons, discount banner)
- **Accent:** muted pink/red for sale badges and out-of-stock
- **Type:** geometric sans, rounded (Poppins or similar). Large light-weight headings
- **Shapes:** subtly rounded corners, generous whitespace, light borders
- **Logo:** illustrated dog with dumbbells, centred in header

### Header

Centred logo, left nav (`HOME · BLOG · SHOP · CROSSFIT ▾`), right icon cluster: search, cart with count badge, wishlist with count badge, account, second search icon. Sticky on scroll.

*Note: two search icons in the header appears to be a theme quirk — worth dropping one in the rebuild.*

### Homepage

1. Hero slider with CTA button, dot pagination
2. Intro heading + paragraph — "GymDog Fitness – Malta's CrossFit Portal"
3. "Latest Articles" carousel — author, date, title, excerpt, arrow nav
4. Promo banner — headline, copyable discount code chip, instruction text
5. Product carousel — image, title, price, out-of-stock badge, arrows
6. Trust badges row (4): Amazing Value Every Day · Successful Customer Service · All Payment Methods · Free delivery for purchases over €50 *(Malta only)*
7. Dark green footer
8. Cookie consent banner (bottom-left)

### Shop / category page

Breadcrumbs · page title ("Malta's Crossfit shop") · promo box with CTA

**Sidebar filters:** product search, price range slider with min/max display, colour swatches with result counts (Black 5, Blue 4, Green 3, Grey 1, Pink 1…), collapsible category tree, brand filter

**Toolbar:** "Showing 1–12 of 17 results" · per-page selector (9/12/18/24) · grid density switcher (1–5 columns) · sort dropdown

**Product tiles:** image, SALE badge, discount % badge, title, price, out-of-stock state

### Product page

Vertical thumbnail rail (left) + main gallery · breadcrumbs · title · price · **colour swatches** · **size buttons** (M/L/XL) · quantity stepper · Add to cart · wishlist heart · Buy Now · **Google Pay button** · Delivery & Return accordion · Size Guide accordion · SKU / Categories / Brand metadata · social share (Facebook, LinkedIn, WhatsApp) · Trusted Seller Description block

---

## Issues found

1. **Main product images render blank above the fold.** On the shop grid and product detail page, the primary image area is empty while thumbnails load correctly; images appear once scrolled. No JavaScript errors in console, so this looks like lazy-loading not firing on initial paint rather than missing files. Worth confirming — a blank product image above the fold directly costs conversions.

2. **SSL is on Cloudflare "Full" (not strict)** as a temporary measure. Get an Origin Certificate installed and move back to Full (strict).

3. **`/about-me/` contains Ninetheme demo content**, including images hotlinked from `ninetheme.com`. Delete rather than migrate.

4. **`/category/uncategorized/`** is indexed. Clean up during migration.

5. **`SKU: N/A`** on the product page — SKUs aren't populated. Worth fixing during product re-entry; useful for stock management and any future integrations.

6. **Stale promo** — homepage shows "5% discount during November!" in July. Static content that nobody updated. In the new CMS this should be a scheduled banner with a validity window.

---

## Implications for the rebuild

**Migration is small.** 46 URLs, 17 products, 10 posts. The content migration in Phase 3 is realistically 2–3 days, not two weeks. Revise the estimate down.

**Variants matter from day one.** The Velites grips have colour *and* size options. The variant model can't be deferred.

**Categories nest.** Two levels confirmed, so the category model needs hierarchy from the start.

**Brands are a first-class taxonomy** with their own indexed URLs — already accounted for in the schema.

**Wishlist is an existing feature** with a header count badge. Either rebuild it or make a deliberate decision to drop it.

**Filtering is fairly rich** — price range, colour, category, brand, with live result counts. Colour filtering implies variant options need to be filterable at the product-list level, which is a specific query design, not an afterthought.

**Free shipping over €50, Malta only** — a real shipping rule that needs modelling, and confirms shipping is Malta-focused.

**A payment gateway already exists** (Google Pay is live). Identify it before Phase 2.

---

## Recommended URL strategy

Preserve everything. Cheap to do, and it protects the existing SEO.

| Content type | Current pattern | Keep? |
|---|---|---|
| Products | `/product/{slug}/` | ✅ Keep exactly |
| Product categories | `/shop/{cat}/{subcat}/` | ✅ Keep exactly |
| Brands | `/product-brands/{slug}/` | ✅ Keep exactly |
| Posts | `/{slug}/` (root) | ✅ Keep exactly |
| Post categories | `/category/{slug}/` | ✅ Keep exactly |
| Pages | `/{slug}/` | ✅ Keep exactly |
| CrossFit guides | `/{slug}/` | ✅ Keep URL, change content type |

Only genuine change: `/about-me/` and `/category/uncategorized/` get removed, with 301s to `/about-us/` and `/blog/` respectively.

With trailing-slash-preserving routes and matching patterns, **the redirects table stays nearly empty** — which is the ideal outcome.
