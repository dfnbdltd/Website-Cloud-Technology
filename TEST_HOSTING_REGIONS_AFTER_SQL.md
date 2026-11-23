# Agent instructions: test Hosting regions (SQL already run by hand)

Project folder: `cloudtechnology` (the folder that contains `artisan`). Run all commands there.
Rules: use `php80` for EVERY php command (default php 8.4 breaks Laravel 6). There is no `artisan test` in Laravel 6: use `php80 vendor/bin/phpunit`.
Do NOT edit code. Report each step as PASS or FAIL with the exact error text.

## Step 1: confirm the SQL is in place (do NOT run `migrate`)
1. `php80 artisan tinker --execute="echo Schema::hasColumn('hosting_plans','region') ? 'REGION_OK' : 'REGION_MISSING';"`
   Expected: `REGION_OK`. If `REGION_MISSING` stop and report.
2. `php80 artisan tinker --execute="echo json_encode(DB::table('hosting_plans')->select('region','type',DB::raw('count(*) c'))->groupBy('region','type')->get());"`
   Expected: only region `local`: shared 5, vps 4, dedicated 5.
3. `php80 artisan tinker --execute="echo DB::table('migrations')->where('migration','like','%add_region_to_hosting_plans')->count();"`
   Expected: `1`. (If `0`: report it; it is harmless, only tidiness.)
4. `php80 artisan view:clear && php80 artisan route:clear && php80 artisan config:clear`
5. `php80 artisan route:list --name=hosting` -> must list hosting.wordpress, hosting.region, admin.hosting.plans (+ .save .copy .create .store .edit .update .visibility .delete). No error.

## Step 2: automated tests
`php80 vendor/bin/phpunit --filter=HostingPlanCmsTest`
Expected: 23 tests, all pass. Paste the summary line and every failure message.
Then `php80 vendor/bin/phpunit` (whole suite). Known unrelated failures: AdminApiConnections 1, DomainRegistrationService 7, DomainTransferSync 1, DomainTransferWorkflow 1, InvoiceWorkflow 1, MyStorePages 1. Report any OTHER failure.

## Step 3: public pages (start `php80 artisan serve` and use curl, or the Herd site)
Expect 200: `/hosting/local/shared`(5 cards), `/hosting/local/vps`(4), `/hosting/local/dedicated`(5), `/hosting/usa/shared`, `/hosting/usa/vps`, `/hosting/usa/dedicated`, `/hosting/wordpress` (the last four show "Plans coming soon").
Expect 301 to `/hosting/local/...`: `/hosting/shared`, `/hosting/vps`, `/hosting/dedicated`.
Expect 404: `/hosting/mars/shared`, `/hosting/local/wordpress`.
Check the HTML of `/hosting/wordpress` contains `site-nav-hosting`, `hosting-rail`, `Local Hosting`, `USA Hosting`, and does not contain `Web Hosting`.

## Step 4: client area (customer logged in)
`/store/hosting/shared` (200, has Plan E), `/store/hosting/shared?region=usa` (200, "Plans coming soon"), `/store/hosting/wordpress` (200, "Plans coming soon"), `/store/hosting/dedicated` (200).

## Step 5: browser checks (only if you have a browser; otherwise say "not checked")
1. Desktop: hover Hosting -> dropdown with Local Hosting >, USA Hosting >, WordPress Hosting. Hover Local -> flyout Shared/VPS/Dedicated to the right; moving the mouse into the flyout must not close it.
2. Mobile width: hamburger -> Hosting -> tap Local Hosting expands its items.
3. Admin `/admin/hosting-plans`: tabs Local 14 / USA 0 / WordPress 0.
   a. Local > Shared: change Plan A monthly price (note the old one), Save changes -> "Saved 5 card(s)"; check `/hosting/local/shared`; restore the old price.
   b. USA > Shared > "Copy from Local Shared" -> 5 hidden cards; public USA page still "coming soon"; tick Show on one + Save -> it appears on `/hosting/usa/shared` only.
   c. WordPress > "+ Add WordPress card": name `WP Starter`, Monthly 350 On sale, Yearly 3500 On sale, features `1 website`, `10 GB NVMe storage`, tick Show -> `/hosting/wordpress` shows the card with price 350 and Monthly/Yearly tabs.
   d. Edit WP Starter > Features > Yearly: untick "Same as Monthly", add `Free SSL`, Save -> Yearly tab shows Free SSL, Monthly does not.
   e. Delete the test cards (USA copies, WP Starter) so the data is clean.

## Step 6: report
PASS/FAIL per step, the phpunit summaries, and for each failure the log lines from `storage/logs/laravel.log`.
