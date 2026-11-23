# Test plan: Hosting → Local / USA / WordPress  (for the DeepSeek agent)

Environment: Windows + Herd, PHP 8.0, project root = the folder with `artisan`.
Run every command from the project root. Report each step as PASS / FAIL with the exact output of any failure.
Do NOT edit any code. If something fails, paste the full error and stop that step.

## 0. Prepare
1. `composer install` (only if `vendor/` is missing)
2. Database: use ONE of these (not both is fine, both are safe):
   - `php artisan migrate`  → expect: `Migrating: 2026_11_23_000000_add_region_to_hosting_plans` then `Migrated`.
   - OR run `database/hosting_regions.sql` in phpMyAdmin (STEP 0 must return 0 rows; then STEP 1–4).
3. `php artisan view:clear && php artisan route:clear && php artisan config:clear`
4. `php artisan route:list --name=hosting` → expect these names: `hosting.wordpress`, `hosting.region`, `hosting.shared`, `hosting.vps`, `hosting.dedicated`, `admin.hosting.plans`, `admin.hosting.plans.save`, `admin.hosting.plans.copy`, `admin.hosting.plans.create`, `admin.hosting.plans.store`, `admin.hosting.plans.edit`, `admin.hosting.plans.update`, `admin.hosting.plans.visibility`, `admin.hosting.plans.delete`. No error.
5. SQL check (phpMyAdmin or `php artisan tinker`): `SELECT region, type, COUNT(*) FROM hosting_plans GROUP BY region, type;` → expect only `local`: shared 5, vps 4, dedicated 5.

## 1. Automated tests
`php artisan test --filter=HostingPlanCmsTest`
Expected: all tests pass (about 22). Paste the full summary. If any fail, paste each failure message and the line it points at.

Then the whole suite: `php artisan test` → report totals. (Failures unrelated to hosting: just list their names.)

## 2. Public pages (start `php artisan serve` or open the Herd site)
For each URL expect HTTP 200 (no Blade/500 error):
| URL | Expect |
|---|---|
| `/hosting/local/shared` | Old shared design, 5 cards (Plan A–E), prices as before |
| `/hosting/local/vps` | 4 cards |
| `/hosting/local/dedicated` | 5 cards |
| `/hosting/usa/shared`, `/hosting/usa/vps`, `/hosting/usa/dedicated` | "Plans coming soon" card with two buttons |
| `/hosting/wordpress` | "Plans coming soon" card (new light design page header "WordPress Hosting") |
| `/hosting/shared` | 301 redirect to `/hosting/local/shared` (same for `/hosting/vps`, `/hosting/dedicated`) |
| `/hosting/mars/shared` and `/hosting/local/wordpress` | 404 |

## 3. Navbar (desktop width ≥ 1000px)
1. Hover **Hosting** → a white rounded dropdown shows: Local Hosting ▸, USA Hosting ▸, WordPress Hosting.
2. Hover Local Hosting → a flyout appears to the right with Shared Hosting / VPS Hosting / Dedicated Hosting. The hovered row turns grey. Move the mouse sideways into the flyout: it must NOT close.
3. Click Local → Shared → goes to `/hosting/local/shared`. Same check for USA → VPS (`/hosting/usa/vps`) and WordPress (`/hosting/wordpress`).
4. Mobile width (≤ 760px): open the hamburger → Hosting → tapping "Local Hosting" expands its three items (tap again to close); the other items are links.
5. The word "Web Hosting" must not appear in the navbar.

## 4. Left switcher
Scroll down past the banner on `/hosting/local/shared` (screen ≥ 1180px wide): the left rail appears with Shared / VPS / Dedicated (active one highlighted), then a divider and "USA" and "WordPress" jumps. On `/hosting/usa/vps` the "other region" jump says Local. On `/hosting/wordpress` the rail shows Local / USA / WordPress.

## 5. Admin CMS  (log in at `/admin/login`)
Sidebar → Hosting plans (`/admin/hosting-plans`).
1. Three region tabs (Local Hosting 14, USA Hosting 0, WordPress Hosting 0) and Shared / VPS / Dedicated sub-tabs (for Local and USA).
2. **Bulk edit:** Local → Shared. Change Plan A's Monthly price to `123`, set its Order to 9, press **Save changes** → green message "Saved 5 card(s)". Reload `/hosting/local/shared`: Plan A shows ৳123 on the Monthly tab and moves to the new position.
   Then set Plan A's Monthly status to *Coming soon* → Save → public Monthly tab for Plan A shows "Coming soon". Set it back to *On sale* with the original price. (Note the original price first.)
3. **Copy:** USA Hosting → Shared → press **Copy from Local Shared** → "Copied 5 card(s)". The cards show "Hidden". Public `/hosting/usa/shared` still says "Plans coming soon". Tick **Show** on one card, change its price, Save → the card now appears publicly on `/hosting/usa/shared`, and does NOT appear on `/hosting/local/shared`.
4. **Add (WordPress):** WordPress tab → **+ Add WordPress card**. Name `WP Starter`, price Monthly 350 / Yearly 3500 (status On sale), Feature rows on the Monthly tab: `1 website`, `10 GB NVMe storage`. Tick "Show this card on the website" and Create. `/hosting/wordpress` shows the new simple card grid with the price ৳350, tabs Monthly/Yearly only (2 Years/3 Years tabs appear only when a card has them not hidden: statuses default to *Coming soon*, so they do appear, showing "Coming soon").
5. **Features per period:** edit WP Starter → Features → Yearly tab → untick "Same as Monthly", add `Free SSL`, save. Public Yearly tab shows Free SSL, Monthly tab does not. Tick "Same as Monthly" on Yearly again → Save → Yearly shows the Monthly list.
6. **Crossed feature:** in the Monthly list untick "Available" on one row → public shows it struck-through with a ×.
7. **Delete:** delete the USA copies / WP Starter with Delete (confirm) → counts drop; public pages return to "Plans coming soon".
8. Validation: add a card with an empty name → error banner "The name field is required."

## 6. Client area (log in as a customer)
`/store/hosting/shared` → pills Local / USA / WordPress and a Shared / VPS / Dedicated row; "See more" goes to `/hosting/local/shared`. `/store/hosting/shared?region=usa` and `/store/hosting/wordpress` load (empty or with cards). Sidebar group is called "Hosting" and has WordPress Hosting.

## 7. Safety check: deploying code BEFORE the SQL
(Only on a scratch DB copy.) Drop the column: `ALTER TABLE hosting_plans DROP INDEX hosting_plans_region_index, DROP COLUMN region;` and `DELETE FROM migrations WHERE migration LIKE '%add_region_to_hosting_plans';`
Expected: `/hosting/local/shared` still works with 5 cards, `/hosting/usa/shared` shows "coming soon", `/admin/hosting-plans` shows the yellow "Run the region SQL first" notice, no 500 anywhere. Then run the SQL again.

## 8. Send back
Section-by-section PASS/FAIL, the test summary from step 1, and screenshots (or a description) for steps 3, 4 and 5.4.
