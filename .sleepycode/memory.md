# cloudtechnologybd — project knowledge

## Stack / run
- Laravel 6.20, PHP ^7.2.5|^8.0. Plain CSS/JS in public/css + public/js. **NO build step in use**: views never
  call `mix()`; `webpack.mix.js` compiles resources/js/app.js (just `require('./bootstrap')`) and an EMPTY
  `resources/sass/app.scss`. `public/css/app.css` is hand-written. Do not `npm run dev`.
- Local PHP CLI is 8.4 → `php artisan` fine, plain `vendor/bin/phpunit` FATAL. Use
  **`php80 vendor\bin\phpunit`** (`C:\Users\azizm\.config\herd\bin\php80\php.exe`). PHPUnit is 9.6.
- **SUITE BASELINE (measured): 223 tests, 1498 assertions, 12 red = 10 failures + 2 errors.** All pre-existing
  and unrelated to views/CSS/SSL work: `ResellerClubDomainContacts::extractDefaultContactId()` missing (9 in
  DomainRegistrationServiceTest + `AdminApiConnectionsTest` expects `/contacts/default.json`),
  `DomainTermPricingTest`, `InvoiceWorkflowTest::testCustomerPdfHidesPurchaseAndProfit`,
  `DomainTransferWorkflowTest::testTransferLandingShowsRequirementsAndSubmitsToCheckout`, and **two real My
  Store regressions** from commit `9d77c03 "reg2"` — `MyStorePagesTest::testDomainPageShowsTheFullDetailOfEvery
  Domain` (view says `RENEWAL`, test wants `RENEWAL PRICE`) and
  `::testDomainPageListsPaidTransferRequestsSeparatelyFromOwnedDomains` (`status_message` deleted from
  store/domains.blade.php). Don't chase these unless asked.
- Views are standalone Blade (no layouts) EXCEPT store pages →
  `@extends('store.layout', ['pageTitle'=>…,'navActive'=>…,'topbarTitle'=>…,'hideStoreAlerts'=>…])`, section
  `store`, `@stack('head')`, `@stack('scripts')`. Partials: head (app.css+workspace.css+coming-soon.css),
  sidebar (sidebar-nav.css, must load AFTER domains.css), topbar, admin-sidebar, logo, chat, coming-soon-modal,
  client-area-menu, site-navbar, favicon, spinner, spinner-overlay, ssl-plans, domain-transfer.
- Files are CRLF in git. **`replace_text` fails on CRLF files.** Working pattern: a temp `php80` script in
  .sleepycode/ that normalises to LF, replaces an exact single occurrence, restores CRLF (also used for JSON
  patch specs). Then `php80 -l`. New files written with `write_file` arrive as LF — convert them to CRLF too.
- **Never `write_file` an existing CSS/asset file.** Read it, then append.
- **Blade trap (hit once):** a multi-line `@php ... @endphp` block inside a `@foreach` in a partial that also
  uses inline `@php(...)` made the compiler emit raw `@php` text and then fail with
  `unexpected token "endforeach"`. Symptom: one compiled view fails `php -l`. Fix: compute in the SERVICE or
  controller, keep the partial a plain loop. Always run `view:cache` + `php -l` on **every**
  `storage/framework/views/*.php` after touching Blade.
- **`SslPlanService::$tablesMissing` is a static memo.** It survives the whole process, so once something
  (a test that drops tables, a worker) sets it true, the app silently serves the config fallback forever.
  Call `SslPlanService::resetSchemaCache()` after any schema change. This caused a real order-dependent test
  bug — first test passed alone, failed in suite.
- Browser: after /logout a fresh page load is needed (CSRF 419). Viewport-switch geometry can report the
  previous layout; reload after switching viewports. To screenshot an admin page without the password, render
  it server-side to a file, copy into `public/`, then open over http (not file://, or CSS is ORB-blocked).
- Local shortcuts in routes/web.php: `GET /__probe-login` (seeds session('customer') → /mystore) and
  `GET /__probe-rowcheck`. **Remove before production.**
- User commits work as they go, and has external tooling that commits `.sleepycode/` scratch files — so
  `git status` may show less/more than expected. Clean up your own scratch files.
- `learn/` and `docs/` and `agent.md` are gitignored (local-only).

## SSL plans — CMS, live ResellerClub pricing, per-feature visibility
### Structure
- Tables `ssl_plans` (+nullable `resellerclub_plan_id` from
  `2026_11_13_000000_add_resellerclub_plan_id_to_ssl_plans`) and `ssl_plan_features`
  (`ssl_plan_id, feature, shown_to_customers, is_available, sort` — visibility column from
  `2026_11_14_000000_add_shown_to_customers_to_ssl_plan_features`).
- `SslPlanService::plans()` → `resolve($plan, $live)` → array consumed by
  `partials/ssl-plans.blade.php`: `id,key,name,tagline,badge,buy_route,monthly,renewal,features[],terms[N],
  default,source('live'|'local')`. **Never carries cost/profit — those are admin-only.**
- Live path: `round(usd_per_month × months × rate)`, convert once/round once (rounding the USD first gives
  ৳1,140 instead of ৳1,138 for $0.79). Terms = only tenures ResellerClub sells (12mo→1y, 24mo→2y; NO 3-year).
  `SslPlanService` calls **`sslSellingPricing()` only** (1 HTTP call); admin uses `sslPlanPricing()` (both
  price endpoints) for cost + profit %. Profit % = (selling−cost)/cost×100, computed here, null when either
  is missing or cost ≤ 0.
- Kill switch `services.resellerclub.ssl_live_pricing` (`RESELLERCLUB_SSL_LIVE_PRICING`, default true).
- Fallback ladder: no link → typed BDT · kill switch off · not configured · API down · 12-mo selling missing ·
  rate ≤ 0 → typed. A plan with typed price ≤ 0 is dropped from the customer list (no free cert).
- Migration pattern in this folder: **no `dropColumn`** (Laravel 6 + SQLite needs doctrine/dbal, absent) —
  rebuild the table instead. Add-column migrations are fine and are `hasColumn`-guarded.
- Plan ID map (matched by `plan_key`, re-runnable, only fills NULLs): positive-ssl→300, comodo-ssl→299,
  positive-ssl-wildcard→301, ev-ssl→672. Admin form requires the ID (price is never typed).
- Locally `RESELLERCLUB_ENABLED=false` with empty key, so the live API is unverifiable here; everything was
  verified against the captured fixtures + `buildSslPricing()` unit tests.
- Tests: `tests/Unit/SslPricingCalculatorTest.php`, `tests/Feature/SslLivePricingTest.php`,
  `tests/Feature/SslPlanCmsTest.php`, `tests/Feature/SslPlanFeatureVisibilityTest.php`.

### Admin editor markup contract (rebuilt at user request — keep it)
- **Single column, max-width 900px.** The old right rail (live preview + visibility note + order help) was
  removed at the user's request; `is_visible` and `sort` moved into a compact "4 Publishing" panel because
  omitting them would unpublish the plan and zero its order.
- Feature row = `grid-template-columns: minmax(0,1fr) 38px 38px` (text | eye | ×) — **icons only, no visible
  labels or helper text**. Responsive: 36px under 640px, 34px under 420px; the text input shrinks, the two
  icon columns never wrap. Rows must stay ONE line — verify `getBoundingClientRect().top` spread < 6px.
- `.ssl-eye` = checkbox laid over an inline SVG (eye + `<circle class="ssl-eye-dot">` +
  `<line class="ssl-eye-off">`). Checked = green eye, no slash. Unchecked = grey eye WITH slash + struck-out
  text via `.is-hidden-feature`. Meaning lives in `aria-label` + `title` only.
- **Never use a `.ssl-sr`-style screen-reader class in this admin view** — that stylesheet (`ssl-plans.css`)
  isn't loaded there, so the "hidden" text renders visibly and destroys the row grid. That was the bug.
- **Checkbox sentinel:** each toggle also posts `<input type="hidden" name="features[N][shown_marker]" value="1">`.
  Contract in `AdminController::validatedSslPlan()`: `shown=1` → visible; `shown_marker` without `shown` →
  explicitly hidden; **neither → stays visible** (older forms/scripts, and the pre-column behaviour that
  `SslPlanCmsTest` pins). Without the marker an unchecked box is indistinguishable from "not posted".
- Delete = the row is simply omitted from the POST; `syncSslPlanFeatures()` deletes-then-recreates, so it
  persists `shown_to_customers` through `$plan->features()->create($feature)`.
- `is_available` is kept in lockstep with visibility (hidden ⇒ is_available=0) so any older reader of that
  column agrees. Hidden rows are dropped in `SslPlanService::features()` (not in Blade).

## Post-auth redirects
- `App\Support\AuthIntent` = session `url.intended` + `url.intended_at`, 24h TTL, same-site paths only
  (`sanitize()` rejects `//host`, `/\host`, other hosts/schemes, control chars). API: `remember`, `forget`,
  `replace`, `peek`, `pull`.
- Checkout guest panel → `route('login'|'register', ['return_to' => '/domains/checkout'])`;
  `EnsureCustomer` → `route('login', ['return_to' => current URI | referer])`.
- `AuthController::intendedDestination()`: explicit return_to → else `/domains/checkout` when a `domain_cart`
  exists → else null. `signIn()`/`verifyEmail()` redirect via `AuthIntent::pull(...)`. Plain sign-in (no cart,
  no return_to) still → dashboard. `email.verified` meta-refreshes to the target.
- `ReturnTo` is the sibling mechanism for back buttons (`?back=`, same sanitiser, no nesting).
- Tests: `tests/Feature/PostAuthRedirectTest.php`, `tests/Feature/InvoiceBackLinkTest.php`.

## Domain transfer page — partials/domain-transfer.blade.php + public/css/domain-transfer.css
- One partial, two surfaces: `.dt-page--public` (`/services/domain-transfer`, navbar+footer) and
  `.dt-page--client` (`/client/coming-soon/domain-transfer`, store layout, `.dt-guide` drops its band).
- `.dt-guide-inner` max-width **880px** = width of `.dt-transfer-panel`.
- Steps: `<div class="dt-transfer-panel">` wrapping `<ol class="dt-transfer-steps">`; each `li` = grid
  node + copy, `li > button` is the round node (JS click-to-select walkthrough toggles is-active/is-complete).
- Connector is ONE pseudo-element per row, `li:not(:last-child)::after`, in the node column; solid gradient,
  colour by state. Row backgrounds live on the CONTENT cell so nothing hides the rail.
- Responsive knobs are CSS vars on `.dt-transfer-steps` (≤620px: node 40, inset 10, pad 13).
- `li` has no `tabindex`; the button is the only tab stop.

## My Store (reworked — /mystore = All Items + 7 section pages)
- `App\Support\StoreInventory` is the single data layer: `sections()` (7 × key/label/title/kicker/icon/tone/
  nav/url/blurb/empty/empty_short/cta), `paidOrders()`, `domains()`, `transfers()`, `service($key)`
  (hosting|ssl|email|sms from **paid invoice lines** by `invoice_items.item_type` prefix), `invoiceMap()`,
  `payments()` (Topup rows + Payment rows, **plain arrays**, sorted desc), `paymentTotals()`, `counts()`,
  `paidToDate()`, `pendingTopupCount()`. NOTE: `payments()` must stay plain arrays — Eloquent
  Collection::merge() calls getKey() on arrays.
- `MyStoreController`: index, domains, domainDetails, domainActivity, updateNameservers,
  updateOrganizationConsent, hosting, service{ssl|email|sms}, invoices, payments.
- Views: `store/layout` + `store/index` (balance row + 7 serial cards) + `domains|service|invoices|payments`
  + `domain-details` (899 lines) + `domain-activity` + `coming-soon`; partials card/empty/page-head/service-rows.
- Invoices section = paid domain orders + their invoice document. Balance cards ONLY on All Items.
- CSS at tail of `public/css/store.css`: tone vars, `.store-card-grid` 3→2→1, `.store-card*`,
  `.store-page-head`, `.store-item/.store-facts/.store-fact`, `.store-totals`, `.store-table.cols-*`.

## Client sidebar — partials/sidebar.blade.php + public/css/sidebar-nav.css
- Accordion via `grid-template-rows: 0fr → 1fr` on `nav > .subnav` + one `.subnav-inner`; one group open at a
  time; Escape closes; aria-expanded/controls. `active` = `open` only; `openOnly()` is the only opener.
- My Store submenu always lists All Items, Domain, Web Hosting, SSL, Business Email, SMS, Invoices, Payments.
- **All three group submenus are ICON-LESS TREES**: parent rows keep `<b>` icons, child rows carry only
  `<span>label</span>`. One selector list over
  `#domainRegistrationSubnav, #hostingPackageSubnav, #myStoreSubnav` (2-id specificity beats domains.css):
  `border-left:0;padding-left:0`, rows `margin-left:16px`, `::before` = vertical segment + dash,
  `:not(:last-child)::after` continues the rail. MUST reset `background:none;box-shadow:none;
  border-radius:0 0 0 9px` — domains.css paints `.subnav a:before` as a glowing blue dot.
- NAVY PILL needs
  `#clientSidebar:not(.collapsed) nav > a.service-nav-item.active{background:#17365e;border-color:#17365e;color:#fff}`
  (domains.css `.service-group-start:not(.open)` ties on specificity and wins by order otherwise).
- Mobile ≤900px off-canvas drawer; ≤520px sidebar-nav.css fixes domains.css making it static. The **`nav`
  element** is the scroll container in the drawer.
- Row rhythm: `nav > a` 44px, padding 6px 12px, gap 10px, 30px icon tile; child rows 40px, radius 10px.

## Public navbar — partials/site-navbar.blade.php + public/css/site-navbar.css
Rules `#site-navbar`-scoped; includes the coming-soon modal. Order: Home · Domains · Web Hosting · SSL ·
Business Email · SMS Service · Support. Tiers: ≥1401px one row 1400px + phone; 1201–1400px 1240px, nav
14.5px/9px, phone → handset icon; ≤1200px nav drops under logo; ≤560px phone text + user name hidden.
Drawer cut-off 960px.

## Domain / shop logic
- Catalog from `ResellerClubClient` (5-min price caches + BDT fallback). `services.test_domains` is referenced
  by 6 code sites but **is NOT defined in config/services.php** → the shortcut is inert.
- Cart in session `domain_cart` (keyed by domain; holds order_type/price/purchase_price/renewal_price/years,
  transfers add an encrypted `transfer_auth_code`). Registrations and transfers can't share a cart.
- `placeOrder`: validates, preflights, `createPendingPayment`, one `DB::transaction` (wallet `lockForUpdate`,
  DomainOrder + items + Payment) → `InvoiceService::createForOrder` → wallet registers immediately +
  `Notifier::send(InvoiceMail)`; bKash → `bkash.pay`; manual → confirmation page.
- Customer auth = Auth + `session('customer')` snapshot; email verification = one-time tokens in
  `email_verification_tokens` (raw SQL, not a migration), 300s resend cooldown. Admin = config creds +
  `session('admin')`, re-checked inline in 35 places (no admin middleware); **POST /admin/login has no
  throttle**.
- Invoices: `InvoiceService` is the only writer; `item_type` produced today: `domain-registration`,
  `domain-transfer`, `topup`. Admin editor also accepts `domain-renewal`, `hosting`, `manual`.
- Hosting/SSL/email/SMS **checkouts do not exist** — My Store shows empty states until they bill invoices.

## Verification techniques that worked here
- Temp PHP helper in .sleepycode/ to create AND delete throwaway customers (`*.check@example.test` /
  `Check#12345`) plus orders/topups/invoices, then delete them.
- Render an admin page server-side (set `session('admin')`, `$app['session']` + `HttpKernel::handle`) to a
  file → copy to `public/` → measure with `browser_evaluate`/`browser_geometry` alongside
  `browser_screenshot`. Beats typing an admin password into a form.
- When a feature test 500s, mirror it in a scratch test with `$this->withoutExceptionHandling()` for the real
  stack trace, then delete the scratch test.
- **Assert on text unique to the fixture** (`UNIQUE-…`). Seeded features repeat across plans, so
  `assertDontSee('1 Sub-domain')` fails for the wrong reason. Also count controls only in the markup
  (slice before `<script>`) — the inline JS template shares class names.
- `run_command` sometimes returns empty stdout for `&`-chained commands; redirect to a file and `type` it.
  `findstr /n @media public/css/domains.css` is useless (4 physical lines, minified) — use PowerShell `-Raw`.
- Pixel-proof purely visual work: full-page screenshot at deviceScaleFactor 1, then sample coordinates in
  PHP GD (`imagecolorat`). Computed-style checks (`getComputedStyle(el,'::before')`) catch inherited
  decoration a visual pass misses.
