# cloudtechnologybd — project knowledge

## Stack / run
- Laravel 6.20, PHP ^7.2.5|^8.0. Plain CSS/JS in public/css + public/js. **NO build step in use**: views never
  call `mix()`; webpack only compiles resources/js/app.js (just bootstrap) and an EMPTY app.scss. Do not
  `npm run dev`.
- Local PHP CLI is 8.4 → `php artisan` fine, plain `vendor/bin/phpunit` FATAL. Use
  **`php80 vendor\bin\phpunit`** (`C:\Users\azizm\.config\herd\bin\php80\php.exe`). PHPUnit 9.6.
- **SUITE BASELINE (measured, latest): 226 tests, 1575 assertions, 12 red = 10 failures + 2 errors.** All
  pre-existing and unrelated to views/CSS/SSL work: `ResellerClubDomainContacts::extractDefaultContactId()`
  missing (9 in DomainRegistrationServiceTest + `AdminApiConnectionsTest` expects `/contacts/default.json`),
  `DomainTermPricingTest`, `InvoiceWorkflowTest::testCustomerPdfHidesPurchaseAndProfit`,
  `DomainTransferWorkflowTest::testTransferLandingShowsRequirementsAndSubmitsToCheckout`, and two real My Store
  regressions from commit `9d77c03 "reg2"` — `MyStorePagesTest::testDomainPageShowsTheFullDetailOfEveryDomain`
  (view says `RENEWAL`, test wants `RENEWAL PRICE`) and
  `::testDomainPageListsPaidTransferRequestsSeparatelyFromOwnedDomains`. Don't chase these unless asked.
- Views are standalone Blade (no layouts) EXCEPT store pages → `@extends('store.layout', [...])`, section
  `store`, `@stack('head')`, `@stack('scripts')`. Partials: head, sidebar, topbar, admin-sidebar, logo, chat,
  coming-soon-modal, client-area-menu, site-navbar, favicon, spinner, spinner-overlay, ssl-plans,
  domain-transfer.
- Files are CRLF in git. **`replace_text` fails on CRLF files.** Working pattern: a temp `php80` script in
  .sleepycode/ that normalises to LF, replaces exact occurrences (assert the expected count!), restores CRLF.
  Then `php80 -l`. New files written with `write_file` arrive LF — convert to CRLF too.
  GOTCHA: in those patch scripts a `<<<'OLD'` heredoc must be closed with `OLD,` inside the call (and `NEW);`
  for the last arg) — a bare `OLD);` leaves the call unclosed and PHP fails with "Unclosed '('".
- **Never `write_file` an existing CSS/asset file.** Read it, then append/patch.
- **Blade trap (hit once):** a multi-line `@php ... @endphp` inside a `@foreach` in a partial that also uses
  inline `@php(...)` made the compiler emit raw `@php` text and then fail. Fix: compute in the SERVICE or
  controller, keep the partial a plain loop. Always run `view:cache` + `php -l` on **every**
  `storage/framework/views/*.php` after touching Blade.
- **`SslPlanService::$tablesMissing` is a static memo** — call `resetSchemaCache()` after any schema change.
- Browser: after /logout a fresh page load is needed (CSRF 419). Viewport-switch geometry can report the
  previous layout; reload after switching viewports. **Admin pages can't be opened directly** (no password) —
  render server-side. A hand-rolled `$app['session']` script FAILS before the kernel boots
  (`Class "session" does not exist`); the reliable way is a **scratch feature test** with
  `$this->withSession(['admin'=>...])->get('/admin/...')->getContent()`, written to `public/__x.html`, opened
  over http, then deleted.
- Local shortcuts in routes/web.php: `GET /__probe-login` (session('customer') → /mystore) and
  `GET /__probe-rowcheck`. **Remove before production.**
- User commits work as they go, and has external tooling that commits `.sleepycode/` scratch files. Clean up
  your own scratch files.
- `learn/`, `docs/`, `agent.md` are gitignored (local-only).
- `findstr`/`&`-chained commands often return empty stdout here. Use
  `powershell -NoProfile -Command "... | Out-String"` or redirect to a file then `type`. `ls` doesn't exist
  (cmd.exe) — use `dir`. `run_command` timeout max is 120s (full suite ≈ 70s, so it just fits).

## Exchange rate (USD → BDT) — the only conversion source
- `App\Services\ExchangeRateService::usdToBdt()` → `['rate','source','updated_at']`. Endpoint
  `https://hexarate.paikama.co/api/rates/latest/USD?target=BDT`, reads `data.mid` (live: **125.0**), caches
  `exchange_rate_usd_bdt` for **21600s (6h)**, falls back to `services.exchange_rate.usd_to_bdt_fallback`
  (120) with `source='fallback'` and `updated_at=null` on any failure. Never throws.
- Consumers: `AdminController::usdToBdtRate()` (dashboard/SSL admin), `AppServiceProvider` view composer
  (`$exchangeRate`, partials.admin-sidebar only), `DomainRegistrationService`, `DomainTransferService`,
  `ResellerClubClient` (convertLivePriceToBdt), `SslPlanService`.
- **Conversion rule everywhere: multiply USD × months × rate FIRST, round ONCE.** Rounding the USD figure
  first is wrong ($0.79 → ৳95/mo but ৳1,140/yr instead of ৳1,138). Per-month display = `round(term / months)`.
- **Tests stub the rate to 120** via `$this->app->instance(ExchangeRateService::class, ...)`; the real 125
  appears only when a scratch test clears the cache and doesn't stub.

## SSL plans — CMS, live ResellerClub pricing, per-feature visibility, YEARLY quoting, TAKA admin
### Structure
- Tables `ssl_plans` (+nullable `resellerclub_plan_id`) and `ssl_plan_features`
  (`ssl_plan_id, feature, shown_to_customers, is_available, sort`).
- `SslPlanService::plans()` → `resolve($plan, $live)` → array consumed by `partials/ssl-plans.blade.php`:
  `id,key,name,tagline,badge,buy_route,monthly,renewal,per_year,renewal_year,features[],terms[N],default,
  source('resellerclub'|'local')`. **Never carries cost/profit — admin-only.**
- **YEARLY QUOTING (customer rule):** every printed figure is annual. `terms[N]['per_year'] =
  round(total / N)`; top-level `per_year` = default (1-year) term's; `renewal_year` = renew USD/mo × 12 × rate
  (live) or `renewal_price × 12` (typed) — never derived from the already-rounded monthly BDT.
  `per_month`/`monthly`/`renewal` stay in the array (admin + term maths) but **nothing customer-facing prints
  them**; tests assert no `/mo` in the markup.
- Live path: only tenures ResellerClub sells (12mo→1y, 24mo→2y; NO 3-year). `sslSellingPricing()` for customers
  (1 HTTP call), `sslPlanPricing()` (both endpoints) for admin cost/profit. Kill switch
  `services.resellerclub.ssl_live_pricing`.
- Fallback ladder: no link → typed BDT · kill switch off · not configured · API down · 12-mo selling missing ·
  rate ≤ 0 → typed. Typed price ≤ 0 ⇒ plan dropped from the customer list (no free cert).
- Migration pattern here: **no `dropColumn`** (Laravel 6 + SQLite needs doctrine/dbal) — rebuild the table.
- Plan ID map (by `plan_key`, fills only NULLs): positive-ssl→300, comodo-ssl→299,
  positive-ssl-wildcard→301, ev-ssl→672.
- Locally `RESELLERCLUB_ENABLED=false` → the live API is unverifiable; verify via captured fixtures +
  `buildSslPricing()` unit tests + stubbed feature tests. Local DB typed fallbacks make the real customer page
  read 1,368 / 4,788 / 7,188 / 15,588 per year.
- Tests: `tests/Unit/SslPricingCalculatorTest.php`, `tests/Feature/SslLivePricingTest.php`,
  `SslPlanCmsTest.php`, `SslPlanFeatureVisibilityTest.php` (**62 tests under `--filter Ssl`**).
- **Plan↔product names are offset in ResellerClub**: comodo-ssl is linked to 299 = $4.00/mo (Premium in their
  panel), so "Comodo SSL ৳4,788/yr" is arithmetically correct; linking it to 300 would make it ৳1,138.

### Admin list (`admin/ssl-plans.blade.php`) — ALL MONEY IN TAKA
- Table heading `ResellerClub pricing (৳ per month)`; its `title` is the ONLY place the rate is stated
  ("live rate of ৳X per USD"). The old explanatory paragraph (`Revenue = selling − reseller price…`) and its
  `.ssl-rc-note` CSS were **deleted at the owner's request** — column `title` tooltips carry the meaning now.
- Conversion lives **in the view only** (`$termTaka`, `$perMonthTaka`, `$taka`): ResellerClubClient and
  SslPlanService were NOT touched. `$termTaka = round(usd × months × $usdRate)`;
  `$perMonthTaka = round($termTaka / months)` (same rule as the card's `per_month`).
- **Revenue is `sellMonth − costMonth`** (difference of the two taka figures already on the row) so the columns
  reconcile by eye; the API's own `profit_amount` is no longer read. **Profit % is a ratio → unconverted.**
- `$usdRate <= 0` ⇒ every money cell prints `—`, never `৳0`.
- `.ssl-rc table{min-width:600px}` inside `.ssl-rc-scroll{overflow-x:auto}`. 4-up table fits at 1920/1280
  (`grid-template-columns: minmax(150px,.6fr) minmax(0,2.4fr) 132px`); at 390px it scrolls in its container
  with no page overflow.
- With the 125.0 live rate + screenshot fixtures, Positive SSL reads ৳83 | 19.7% | ৳16 | ৳99 | ৳1,185.

### Customer card layout / CSS
- `.ssl-price` row = superscript `.ssl-taka` + `.ssl-amount` + `.ssl-per`; `.ssl-per` rides the baseline
  (`align-self:flex-end; margin-bottom:.26em`). `.ssl-amount` is `clamp(34px, 3.6vw, 42px)` — **unchanged for the
  wider yearly figure**; the clamp is `vw`-based so it scales. Do NOT "fix" phantom overflow by forcing 42px onto
  a narrow card: at the tightest 4-up viewport (1081px → 237px card, 188px content) it computes to 38.9px and the
  widest figure "৳15,588/year" needs ~177px.
- Term `<option>`s carry `data-per-year` / `data-total` / `data-savings`; inline script swaps `.ssl-amount` +
  `.ssl-total` on change. No-JS default = the selected (1-year) option.
- Totals line = `৳{total} <span>total for {term}</span>` — the charge, not a per-month figure.

### Admin editor markup contract (keep it)
- **Single column, max-width 900px.** No right rail; `is_visible` + `sort` live in a compact "4 Publishing" panel.
- Feature row = `grid-template-columns: minmax(0,1fr) 38px 38px` (text | eye | ×) — **icons only, no visible
  labels/helper text**. 36px under 640px, 34px under 420px. Rows must stay ONE line (< 6px top spread).
- `.ssl-eye` = checkbox over inline SVG (eye + `.ssl-eye-dot` + `.ssl-eye-off`). Checked = green eye, no slash;
  unchecked = grey eye WITH slash + `.is-hidden-feature` strike. Meaning in `aria-label` + `title` only.
- **Never use a `.ssl-sr`-style screen-reader class in the admin view** — `ssl-plans.css` isn't loaded there.
- **Checkbox sentinel:** each toggle also posts `features[N][shown_marker]=1`. `AdminController::validatedSslPlan()`:
  `shown=1` → visible; marker without `shown` → explicitly hidden; neither → stays visible.
- Delete = row omitted from POST; `syncSslPlanFeatures()` deletes-then-recreates. `is_available` kept in lockstep
  with visibility. Hidden rows dropped in `SslPlanService::features()` (not Blade).
- Plan column shows the NAME only (+ "Hidden" pill). The "no price returned" warning quotes the last saved
  price as `monthly_price × 12` per year.

## Post-auth redirects
- `App\Support\AuthIntent` = session `url.intended` + `url.intended_at`, 24h TTL, same-site paths only
  (`sanitize()` rejects `//host`, `/\host`, other hosts/schemes, control chars). API: `remember`, `forget`,
  `replace`, `peek`, `pull`.
- Checkout guest panel → `route('login'|'register', ['return_to' => '/domains/checkout'])`;
  `EnsureCustomer` → `route('login', ['return_to' => current URI | referer])`.
- `AuthController::intendedDestination()`: explicit return_to → else `/domains/checkout` when a `domain_cart`
  exists → else null. `signIn()`/`verifyEmail()` redirect via `AuthIntent::pull(...)`. Plain sign-in → dashboard.
- `ReturnTo` is the sibling mechanism for back buttons (`?back=`, same sanitiser, no nesting).
- Tests: `PostAuthRedirectTest.php`, `InvoiceBackLinkTest.php`.

## Domain transfer page — partials/domain-transfer.blade.php + public/css/domain-transfer.css
- One partial, two surfaces: `.dt-page--public` (`/services/domain-transfer`) and `.dt-page--client`
  (`/client/coming-soon/domain-transfer`).
- `.dt-guide-inner` max-width **880px** = width of `.dt-transfer-panel`.
- Steps: `.dt-transfer-panel` > `ol.dt-transfer-steps`; each `li` = grid node + copy, `li > button` is the round
  node (JS click-to-select walkthrough toggles is-active/is-complete).
- Connector = ONE pseudo-element per row (`li:not(:last-child)::after`) in the node column; solid gradient,
  colour by state. Row backgrounds live on the CONTENT cell.
- Responsive knobs are CSS vars on `.dt-transfer-steps` (≤620px: node 40, inset 10, pad 13). `li` has no
  `tabindex`; the button is the only tab stop.

## My Store (reworked — /mystore = All Items + 7 section pages)
- `App\Support\StoreInventory` is the single data layer: `sections()`, `paidOrders()`, `domains()`,
  `transfers()`, `service($key)`, `invoiceMap()`, `payments()` (**plain arrays** — Collection::merge() calls
  getKey() on arrays), `paymentTotals()`, `counts()`, `paidToDate()`, `pendingTopupCount()`.
- `MyStoreController`: index, domains, domainDetails, domainActivity, updateNameservers,
  updateOrganizationConsent, hosting, service{ssl|email|sms}, invoices, payments.
- Views: `store/layout` + `store/index` + `domains|service|invoices|payments` + `domain-details` (899 lines) +
  `domain-activity` + `coming-soon`; partials card/empty/page-head/service-rows.
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
Drawer cut-off 960px. NOTE: at 390px `.site-nav-toggle` / `.site-account` extend ~54px past the viewport on
**every** public page (pre-existing; also on /services/domain-transfer — not caused by content pages).

## Domain / shop logic
- Catalog from `ResellerClubClient` (5-min price caches + BDT fallback). `services.test_domains` is referenced
  by 6 code sites but **is NOT defined in config/services.php** → the shortcut is inert.
- Cart in session `domain_cart` (keyed by domain; order_type/price/purchase_price/renewal_price/years;
  transfers add an encrypted `transfer_auth_code`). Registrations and transfers can't share a cart.
- `placeOrder`: validates, preflights, `createPendingPayment`, one `DB::transaction` (wallet `lockForUpdate`,
  DomainOrder + items + Payment) → `InvoiceService::createForOrder` → wallet registers immediately +
  `Notifier::send(InvoiceMail)`; bKash → `bkash.pay`; manual → confirmation page.
- Customer auth = Auth + `session('customer')` snapshot; email verification = one-time tokens in
  `email_verification_tokens` (raw SQL, not a migration), 300s resend cooldown. Admin = config creds +
  `session('admin')`, re-checked inline in 35 places (no admin middleware); **POST /admin/login has no
  throttle**.
- Invoices: `InvoiceService` is the only writer; `item_type` today: `domain-registration`, `domain-transfer`,
  `topup` (admin editor also accepts `domain-renewal`, `hosting`, `manual`).
- Hosting/SSL/email/SMS **checkouts do not exist** — My Store shows empty states until they bill invoices.

## Verification techniques that worked here
- Temp PHP helper in .sleepycode/ to create AND delete throwaway customers (`*.check@example.test`) plus
  orders/topups/invoices.
- **Admin-page proof = scratch feature test** that writes `getContent()` into `public/__x.html`, then
  `browser_navigate` + `browser_evaluate` (read cells) + `browser_geometry` (overflow) + `browser_screenshot`;
  delete the test and the file afterwards.
- Assert on text unique to the fixture (`UNIQUE-…`). Count controls only in the markup (slice before
  `<script>`). When slicing a page you must anchor on the MARKUP (`<div class="ssl-card-list">`), not a class
  name — the same string appears in the page's inline `<style>` and would swallow the sidebar.
- When a feature test 500s, mirror it in a scratch test with `$this->withoutExceptionHandling()`.
- Pixel-proof purely visual work: full-page screenshot at deviceScaleFactor 1, sample coordinates in PHP GD.
- `browser_geometry` can report a stale viewport right after switching; reload, then re-measure. It also
  sometimes reports `documentHeight: 0` — use `document.documentElement.scrollWidth - clientWidth` for overflow.
