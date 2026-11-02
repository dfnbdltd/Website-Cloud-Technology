# cloudtechnologybd — project knowledge

## Stack / run
- Laravel 6.20, PHP ^7.2.5|^8.0. Plain CSS/JS in public/css + public/js (no build step).
- Local PHP CLI is 8.4 → `php artisan` and plain `vendor/bin/phpunit` FATAL (Laravel 6 turns
  deprecations into exceptions). Use **`php80 vendor\bin\phpunit`** (Herd ships php80/php81 on PATH);
  89 tests run there.
- Pre-existing test failures (NOT from our work): DomainTermPricingTest
  (testCustomerCanSelectApiPricedRegistrationTerm, testCatalogUsesApiTermPricesAndApiRenewalRate) and
  InvoiceWorkflowTest::testCustomerPdfHidesPurchaseAndProfit (PDF says "Domain & hosting services",
  test expects "ResellerClub").
- Views are standalone Blade (no layouts). Partials: head, sidebar (client), topbar, admin-sidebar,
  logo, chat, coming-soon-modal, client-area-menu, site-navbar.
- Files are CRLF in git. `replace_text` FAILS on CRLF files — use write_file then re-normalize to CRLF,
  or a temp PHP str_replace script (see .sleepycode pattern used earlier).

## Public navbar (done)
`partials/site-navbar.blade.php` + `public/css/site-navbar.css` (all rules scoped `#site-navbar`; wrapper
sticky top:-39px). Include with `['navActive' => 'home'|'domains'|'hosting'|'support'|null]`.
Used by home, domains index, cart, checkout, hosting shared/vps/dedicated, coming-soon, confirmation,
bKash payment, invoice view. Client area keeps its sidebar; admin unchanged.

## Client sidebar (done) — public/css/sidebar-nav.css, loaded by partials/sidebar.blade.php
All rules are `#clientSidebar`-scoped, so they beat the stacked `.domain-sidebar` rules in domains.css
regardless of order. That file is left untouched.
- Accordion: `grid-template-rows: 0fr → 1fr` on `nav > .subnav` with one `.subnav-inner` child (real
  content height; nothing clipped; any row count). Open state: padding 4px 0, margin-bottom 8px.
- ONE group open at a time (opening Web Hosting closes Domains). The current page's parent keeps
  `.active` via `data-current`. aria-expanded/aria-controls wired; Escape closes; reduced-motion honoured.
- ROW RHYTHM: every `nav > a` is 44px tall (min-height/height), padding 6px 12px, gap 10px, icon tile
  30px — identical in closed/open/active states (domains.css had 38/48/44 mix and the active row grew to
  52px). Labels therefore all start at x=71.
- Child rows (`nav > .subnav > .subnav-inner > a`): 40px tall, radius 10px, padding 8px 14px 8px 10px,
  gap 4px between rows, 20px icon.
- Control: small "+" = plain 14×2px bars in a 24px hit box (::before flat, ::after upright), no
  circle/border/background. OPEN = plain "−" (upright bar collapses: rotate(90deg) scaleX(0)).
  Dark #3f5d7d on light rows; **white #fff** on any `.open` or `.active` row (navy pill rgb(23,54,94)).
  Bar sits 18px from the row's right edge; `.nav-count` badges use the same 18px margin.
- Mobile: ≤900px drawer (glide .32s + backdrop); ≤520px overrides the old `nav{display:none}` so the
  drawer shows the real menu. Nav scrolls; body never overflows horizontally.
- NOTE: browser_geometry right after switching viewport can report a stale layout (main at x=316,
  phantom overflow). Re-measure with browser_evaluate before believing it.

## Domain / shop logic (unchanged)
- Catalog from ResellerClubClient (6h cache + fallback BDT prices). Cart in session `domain_cart`.
  placeOrder: DB txn (wallet lockForUpdate), DomainOrder + items + Payment → InvoiceService::createForOrder;
  session `last_domain_order`. bKash popup via BkashController (create/execute/query/cancel).
- Customer auth = Auth + session('customer') snapshot; client code reads session('customer.id').
  Email verification = one-time tokens (database/email_system.sql, not a migration). DEMO_USER_* env login.
- Admin = config services.admin credentials + session('admin'), checked per method (no middleware).
- Invoices: InvoiceService is the only writer (createForOrder/Topup, markOrderPaid/Unpaid, cancelOrder,
  applyEdit, setStatus, recalculate); mPDF admin landscape / customer portrait; `invoices:backfill`.
- Hosting cards: HostingPlan/HostingPlanFeature + HostingPlanService (DB else legacy fallback), per-term
  prices/names/features/visibility; hosting PURCHASE DOES NOT EXIST yet (Buy → support/register).
- CMS text: Setting::get() from `settings` table, cached; /admin/content.
