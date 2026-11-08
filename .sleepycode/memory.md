# cloudtechnologybd — project knowledge

## Stack / run
- Laravel 6.20, PHP ^7.2.5|^8.0. Plain CSS/JS in public/css + public/js (no build step). No arrow functions needed (7.2 floor).
- Local PHP CLI is 8.4 → `php artisan` fine, but plain `vendor/bin/phpunit` FATAL. Use
  **`php80 vendor\bin\phpunit`**. Suite (155 tests) has PRE-EXISTING red: 9 failures / 2 errors —
  mostly `ResellerClubDomainContacts::extractDefaultContactId()` missing (DomainRegistrationServiceTest,
  AdminApiConnectionsTest), DomainTermPricingTest, InvoiceWorkflowTest::testCustomerPdfHidesPurchaseAndProfit,
  and DomainTransferWorkflowTest expects `60-Day Rule:` but the view says `60-Day Rule`. Verified identical
  at HEAD, so don't chase them when you only touched views/CSS.
- Views are standalone Blade (no layouts) EXCEPT the store pages, which use
  `resources/views/store/layout.blade.php` via `@extends('store.layout', ['pageTitle'=>…,'navActive'=>…,'topbarTitle'=>…])`.
  Partials: head (loads app.css + workspace.css), sidebar (loads sidebar-nav.css AFTER domains.css),
  topbar, admin-sidebar, logo, chat, coming-soon-modal (included by sidebar), client-area-menu, site-navbar.
- Files are CRLF in git. **`replace_text` fails on CRLF files.** Reliable pattern: temp PHP script in
  .sleepycode/ that normalises to LF, replaces, restores CRLF; run with php80, verify with `php80 -l` /
  mb_check_encoding + brace count, then delete.
- **Never `write_file` an existing CSS/asset file** (it replaces the whole file). Read it, then append.
- Browser: after /logout a fresh page load is needed (CSRF 419). Viewport-switch geometry can report the
  previous layout; reload after switching viewports before trusting overflow numbers.
- Local shortcut for browser checks: `GET /__probe-login` (user-committed TEMP-PROBE route in routes/web.php)
  seeds `session('customer')` and redirects to /mystore — enough to view client pages without real auth.
- User commits work as they go, so `git status` may show less than expected.

## Post-auth redirects
- `App\Support\AuthIntent` = "where was I going": session `url.intended` + `url.intended_at`, 24h TTL,
  same-site paths only (`sanitize()` rejects `//host`, `/\host`, other hosts/schemes, control chars).
  API: `remember`, `forget`, `replace` (remember-or-clear), `peek`, `pull`.
- Checkout guest panel → `route('login'|'register', ['return_to' => '/domains/checkout'])`;
  `EnsureCustomer` → `route('login', ['return_to' => current URI | referer])`.
- `AuthController::intendedDestination()`: explicit return_to → else `/domains/checkout` when a
  `domain_cart` exists → else null. `signIn()`/`verifyEmail()` redirect via `AuthIntent::pull(...)`.
  Plain sign-in (no cart, no return_to) still → dashboard. `email.verified` meta-refreshes to the target.
- login/register/verify views forward `return_to` to each other through `$returnTo`.
- Tests: `tests/Feature/PostAuthRedirectTest.php`.

## Domain transfer page — partials/domain-transfer.blade.php + public/css/domain-transfer.css
- One partial, two surfaces: `.dt-page--public` (`/services/domain-transfer`, navbar+footer, `.dt-guide` keeps its
  #f5f8fd→#eef4fb band) and `.dt-page--client` (`/client/coming-soon/domain-transfer`, store layout) where
  `.dt-page--client .dt-guide{padding:16px 0 12px;background:none}` drops the band into the workspace.
- Guide column: `.dt-guide-inner` max-width **880px** = width of `.dt-transfer-panel` so heading, "7 steps" chip,
  panel, prep grid and after-card share one right edge.
- Steps (redesigned): `<div class="dt-transfer-panel">` white card (radius 22, padding 12, soft shadow) wrapping
  `<ol class="dt-transfer-steps">`; each `li` = grid `var(--dt-step-node)` + copy, `li > button` is the round node
  (JS keeps click-to-select walkthrough and toggles is-active/is-complete). Copy lives in
  `.dt-step-body > p.dt-step-text` (15.5/1.62 DM Sans #5c7590; active #10233d 500; complete #6a8298).
  Node: 44px, idle white + #6b7f95 number, active blue gradient (#3f86f0→#1b53c6) + 5px rgba ring, complete
  soft green tint + ✓ via `::before` (span hidden).
- Connector is ONE pseudo-element per row, `li:not(:last-child)::after` in the empty node column
  (`left:calc(inset + node/2 - 1px)`, `top:calc(pad + node)`, `bottom:calc(pad * -1)`) so it reaches the next node
  top across row padding. Solid gradient, colour by state (grey / green complete / blue active) — the old dotted
  rail that looked broken is gone. Row backgrounds live on the CONTENT cell, so nothing ever hides the rail.
- Responsive knobs are the CSS vars on `.dt-transfer-steps` (≤620px: node 40, inset 10, pad 13); "7 steps" chip
  hides ≤620px. Verified no horizontal overflow at 390/820/1280 and rail continuity by sampling screenshot pixels.
- `li` no longer has `tabindex` (button is the only tab stop); JS click/keydown still works through bubbling.

## My Store (reworked — /mystore = All Items + 7 section pages)
- `App\Support\StoreInventory` is the single data layer: `sections()` metadata (7 entries: key/label/title/
  kicker/icon/tone/nav/url/blurb/empty/empty_short/cta), `paidOrders()`, `domains()` (per domain in paid
  orders + order + invoice + payments + expiry), `service($key)` (hosting|ssl|email|sms from **paid invoice
  lines** matched by `invoice_items.item_type` prefix — hosting/ssl+certificate/email+mailbox/sms),
  `invoiceMap()`, `payments()` (ledger: Topup rows + Payment rows, arrays, sorted desc),
  `paymentTotals()`, `counts()`, `paidToDate()`, `pendingTopupCount()`.
  NOTE: `payments()` builds a **plain** array list — Eloquent Collection::merge() calls getKey() on arrays.
- `MyStoreController`: index, domains, hosting, service{ssl|email|sms}, invoices, payments.
  Routes: `mystore` (All Items), `mystore.domains|hosting|invoices|payments`, `mystore.service` (ssl|email|sms).
  Old `CustomerController@myStore` removed.
- Views: `store/layout` + `store/index` (balance row + 7 serial cards via
  `@component('store.partials.card', ['serial'=>1..7,'section'=>…,'count'=>…,'summary'=>…])`) +
  `store/domains|service|invoices|payments` + partials `page-head`, `empty`, `service-rows`.
  Empty sections always render a first-purchase prompt (`.store-empty`); SSL/email/SMS empty CTA opens the
  coming-soon modal. Balance cards appear ONLY on All Items (user decision).
- Invoices section = paid domain orders + their invoice document (user choice, not the invoices table).
- CSS in `public/css/store.css` tail: tone vars (`.tone-blue|green|purple|orange|teal|slate|gold`),
  `.store-card-grid` 3→2→1 cols, `.store-card*`, `.store-page-head`, `.store-item/.store-facts/.store-fact`,
  `.store-totals`, `.store-table.cols-invoices|cols-payments` (5/6-col grids; workspace.css `.table-row` is 4-col).
- Tests: `tests/Feature/MyStorePagesTest.php`.

## Client sidebar — partials/sidebar.blade.php + public/css/sidebar-nav.css
- Accordion via `grid-template-rows: 0fr → 1fr` on `nav > .subnav` + one `.subnav-inner`; one group open at a
  time; `data-current` keeps the current page's group active; Escape closes; aria-expanded/controls.
- My Store submenu ALWAYS lists: All Items, Domain, Web Hosting, SSL Certificate, Business Email,
  SMS Service, Invoices, Payments (real routes, active keys `mystore|my-domains|hosting|ssl|email|sms|
  invoices|payments`). `$hasPurchasedDomain` gate removed.
- **All three group submenus are ICON-LESS TREES** (user request, ref image): Domains, Web Hosting and
  My Store child rows carry no `<b>` glyph — only `<span>label</span>`; the **parent rows keep their icons**.
  CSS block at the end of sidebar-nav.css, one selector list over
  `#domainRegistrationSubnav, #hostingPackageSubnav, #myStoreSubnav` (2-id specificity → beats domains.css):
  each subnav gets `border-left:0; padding-left:0`, rows get `margin-left:16px`, each row draws
  `::before` = vertical segment (top:-4px, height calc(50% + 4px), bottom-left 9px radius) + horizontal dash
  to x=0, plus `:not(:last-child)::after` continuing the rail to bottom:-4px. Measured identical in all
  three: rail x ≈ 40px, text x ≈ 69px, rows 40px, 4px gaps fully bridged, last row has no downward segment.
  MUST reset `background:none; box-shadow:none; border-radius:0 0 0 9px` — domains.css paints
  `.subnav a:before` as a blue dot with a glow that would otherwise fill/skew the connector.
  Accordion still works (one group open, opened group `grid-template-rows` = full height, e.g. 3 rows → 128px).
- Fragment-aware highlighting JS still runs (harmless now) for old `#section` links.
- Row rhythm: `nav > a` 44px, padding 6px 12px, gap 10px, 30px icon tile; child rows 40px, radius 10px.
- NAVY PILL: sidebar-nav.css needs
  `#clientSidebar:not(.collapsed) nav > a.service-nav-item.active{background:#17365e;border-color:#17365e;color:#fff}`
  (domains.css `.service-group-start:not(.open)` ties on specificity and wins by order otherwise).
- Mobile ≤900px off-canvas drawer (`transform:translateX(-100%)`, `.mobile-open`); ≤520px domains.css makes
  the sidebar static — sidebar-nav.css fixes it back to fixed. The **`nav` element** is the scroll container
  in the drawer (not the aside): 8 My Store rows push nav content past 718px, so nav scrolls — fine.

## Public navbar — partials/site-navbar.blade.php + public/css/site-navbar.css
Rules `#site-navbar`-scoped, includes the coming-soon modal (any element with
`class="coming-soon-trigger" data-service="..."` opens "<service> is coming soon").
Order: Home · Domains · Web Hosting · SSL Certificate · Business Email · **SMS Service** · Support.
Fitting tiers (signed-in row needs ~1310px; verified 1024–1920, zero overflow):
≥1401px one row max-width 1400px + phone; 1201–1400px one row max-width 1240px, nav 14.5px/9px, phone →
handset icon; ≤1200px nav drops under the logo; ≤560px phone text and user name hidden.

## Domain / shop logic
- Catalog from ResellerClubClient (6h cache + BDT fallback). `config('services.test_domains')` has
  `wallet-test.ctbd.test` / `bkash-test.ctbd.test` at ৳1 for local testing.
- Cart in session `domain_cart` (keyed by domain). addToCart → checkout.
  placeOrder: DB txn (wallet lockForUpdate), DomainOrder + items + Payment → InvoiceService::createForOrder.
- Customer auth = Auth + session('customer') snapshot (AuthController::customerSession).
  Email verification = one-time tokens in `email_verification_tokens` (database/email_system.sql, not a
  migration); local DB has email_* tables, mail.driver=smtp locally. Admin = config creds + session('admin').
- Invoices: InvoiceService is the only writer; `item_type` values today: `domain-registration`, `topup`.
  Hosting SSL/email/SMS checkouts do NOT exist yet (My Store shows empty states until they bill invoices).
- Hosting cards: HostingPlan/HostingPlanFeature + HostingPlanService.

## Verification techniques that worked here
- Temp PHP helper in .sleepycode/ to create AND delete throwaway customers (`*.check@example.test` /
  `Check#12345`) plus orders/topups/invoices, so pages can be checked filled and empty; always delete the
  users + their orders/invoices/payments/topups afterwards.
- Drive flows with `browser_evaluate` fetching POST endpoints with the page's CSRF token; `res.url` shows
  the landing page after redirects.
- When a feature test 500s, mirror it in a scratch test with `$this->withoutExceptionHandling()` to get the
  real stack trace (that is how the Eloquent merge()/getKey() bug was found), then delete the scratch test.
- Computed-style inspection of pseudo-elements (`getComputedStyle(el, '::before')`) catches inherited
  decoration from domains.css that a plain visual check would miss.
- Pixel-proof for purely visual work: `browser_screenshot` a full page (deviceScaleFactor 1, no scroll
  offset), then sample the rail/node/row coordinates in PHP GD (`imagecolorat`) to prove connectors and
  states actually paint instead of trusting CSS alone.
