# Test instructions: Organization Name Consent (ask once) + Latest Activity (all-time)

You are the testing agent. Project: **cloudtechnology** (Laravel 6, PHP 8.0, MySQL, ResellerClub API).
Environment: Windows + Laravel Herd, shell = PowerShell. Run every command from the project root (the folder that contains `artisan`).

Your job is to **run the checks below and report the exact results**. Do **not** edit application code or tests.
If something fails, copy the full error text into the report and continue with the next step.

---

## 0. Safety rules (read first)

1. Several tests use `RefreshDatabase`. That **wipes the database it runs against**. Never run the test suite against the real/dev MySQL database. Step 5 forces an in-memory SQLite database. If SQLite cannot be used, **stop and report it**; do not fall back to the real database.
2. Never run `migrate:fresh`, `migrate:refresh`, `db:wipe` or `db:seed`.
3. Never run anything against the production server. This is local only.
4. Do not change `.env` values for ResellerClub. Do not make real purchases.

## 1. What was changed (for context)

**Feature 1: Organization Name Consent is asked once**
- After the customer clicks **Yes, I agree** or **Cancel** and ResellerClub accepts it, the answer is saved on the domain (`domain_order_items.org_consent_choice` = `accepted` / `declined`, `org_consent_responded_at` = time).
- Right after the click the box stays visible for **5 seconds** (buttons greyed out, green success alert at the top), then fades out.
- On every later visit the box is **not rendered at all**.
- If ResellerClub returns an error, nothing is saved and the box stays so the customer can try again.
- A repeated POST for an already answered domain does **not** call ResellerClub again.

**Feature 2: Latest Activity always shows the whole history**
- ResellerClub has two endpoints: `search-current` (pending + last 24h) and `search-archived` (older). Before, if **either** could not be read, the page said "Activity is temporarily unavailable". Now each endpoint is read independently, so older history shows even when `search-current` is empty/erroring.
- An ERROR answer that only means "no records found" is treated as an empty list.
- Clicking **Manage** (and the View All page) always re-reads ResellerClub instead of using a copy up to 60 s old.
- New helper command: `php artisan domain:activity-probe <domain>` shows what each endpoint returns.

**Files changed or added** (all paths relative to the project root):

```
app/Models/DomainOrderItem.php
app/Http/Controllers/MyStoreController.php
app/Services/ResellerClubClient.php
app/Support/DomainActivity.php
app/Console/Commands/ProbeDomainActivity.php                          (new)
resources/views/store/domain-details.blade.php
public/css/store.css
database/migrations/2026_11_19_000000_add_org_consent_to_domain_order_items.php   (new)
database/org_consent.sql                                              (new, manual SQL for cPanel)
tests/Unit/DomainActivityTest.php
tests/Feature/DomainActivityTest.php
tests/Feature/MyStorePagesTest.php
```

> Note from the developer: PHP was not available where this code was written, so it has **not been executed yet**. Step 3 (syntax lint) and Step 5 (tests) are the first real run. Report every failure exactly.

---

## 2. Environment check

```powershell
php -v
composer --version
Test-Path vendor\autoload.php
Test-Path .env
```

Expected:
- `php -v` shows **PHP 8.0.x**.
- If `vendor\autoload.php` is `False`, run `composer install`. If `.env` is `False`, run `copy .env.example .env` then `php artisan key:generate`.

Confirm the changed files exist:

```powershell
$files = @(
 'app\Models\DomainOrderItem.php',
 'app\Http\Controllers\MyStoreController.php',
 'app\Services\ResellerClubClient.php',
 'app\Support\DomainActivity.php',
 'app\Console\Commands\ProbeDomainActivity.php',
 'resources\views\store\domain-details.blade.php',
 'public\css\store.css',
 'database\migrations\2026_11_19_000000_add_org_consent_to_domain_order_items.php',
 'database\org_consent.sql',
 'tests\Unit\DomainActivityTest.php',
 'tests\Feature\DomainActivityTest.php',
 'tests\Feature\MyStorePagesTest.php'
)
$files | ForEach-Object { '{0}  {1}' -f (Test-Path $_), $_ }
```

Expected: every line starts with `True`.

## 3. PHP syntax lint

```powershell
$php = @(
 'app\Models\DomainOrderItem.php',
 'app\Http\Controllers\MyStoreController.php',
 'app\Services\ResellerClubClient.php',
 'app\Support\DomainActivity.php',
 'app\Console\Commands\ProbeDomainActivity.php',
 'database\migrations\2026_11_19_000000_add_org_consent_to_domain_order_items.php',
 'tests\Unit\DomainActivityTest.php',
 'tests\Feature\DomainActivityTest.php',
 'tests\Feature\MyStorePagesTest.php'
)
$php | ForEach-Object { php -l $_ }
```

Expected: nine lines, each `No syntax errors detected in <file>`.

## 4. Blade, routes, command registration

```powershell
php artisan view:clear
php artisan view:cache
php artisan route:list --name=mystore.domains
php artisan list | Select-String "domain:activity-probe"
```

Expected:
- `view:cache` ends with `Blade templates cached successfully.` (a Blade syntax error would show here).
- `route:list` includes `mystore.domains.organization-consent` (POST) and `mystore.domains.details` (GET).
- `domain:activity-probe` appears in the command list.

Then `php artisan view:clear` again.

## 5. Automated tests (isolated, in-memory database)

Set these for **this PowerShell session only** so the tests can never touch the real database:

```powershell
$env:APP_ENV = "testing"
$env:DB_CONNECTION = "sqlite"
$env:DB_DATABASE = ":memory:"
$env:CACHE_DRIVER = "array"
$env:SESSION_DRIVER = "array"
$env:QUEUE_CONNECTION = "sync"
```

Run, one at a time (stop and report if a command prints "could not find driver": SQLite is missing, do not use MySQL instead):

```powershell
vendor\bin\phpunit tests\Unit\ResellerClubConsentTest.php
vendor\bin\phpunit tests\Unit\DomainActivityTest.php
vendor\bin\phpunit tests\Feature\DomainActivityTest.php
vendor\bin\phpunit tests\Feature\MyStorePagesTest.php
```

Expected (test counts; assertion counts may vary):
- `ResellerClubConsentTest`: `OK (1 test, ...)`
- `Unit\DomainActivityTest`: `OK (21 tests, ...)`
- `Feature\DomainActivityTest`: `OK (10 tests, ...)`
- `MyStorePagesTest`: `OK (17 tests, ...)`

Tests that specifically cover the new behaviour (they must pass):

| Test | Proves |
|---|---|
| `testHistoryOlderThan24HoursStillShowsWhenCurrentCannotBeRead` | archived history shows when `search-current` fails |
| `testRecentActivityStillShowsWhenArchivedCannotBeRead` | the reverse case |
| `testANoRecordsErrorIsAnEmptyEndpointNotAFailure` | "No records found" error = empty list, real errors stay errors |
| `testClientReturnsNullAndCachesNothingWhenBothEndpointsFail` | both down = "unavailable", never "No activity" |
| `testOpeningManageClearsOnlyTheShortCopyAndKeepsTheOutageFallback` | Manage reads fresh, outage fallback kept |
| `testProbeReportsEachEndpointSeparately` | probe helper |
| `testOpeningManageAlwaysReadsFreshFromResellerClubNotTheShortCopy` | details + View All ignore the 60 s copy |
| `testDomainOrganizationConsentCanBeAcceptedOrCancelled` | answer saved as accepted / declined |
| `testConsentBoxShowsUntilAnsweredThenStaysFiveSecondsThenIsGone` | box lifecycle: shown, 5 s auto-hide marker, gone later |
| `testAnsweringTwiceNeverCallsResellerClubAgain` | no second API call, first answer kept |
| `testAFailedConsentCallKeepsTheBoxSoItCanBeTriedAgain` | failure keeps the box, saves nothing |

Now the whole suite (same session variables still set):

```powershell
vendor\bin\phpunit
```

Expected: `OK`. If there are failures in files **not** listed in section 1, report them as "unrelated or pre-existing" with the full message. Do not fix them.

Afterwards clear the session variables:

```powershell
Remove-Item Env:APP_ENV, Env:DB_CONNECTION, Env:DB_DATABASE, Env:CACHE_DRIVER, Env:SESSION_DRIVER, Env:QUEUE_CONNECTION
```

## 6. Migration on the local development database

Only if the project's `.env` points to a **local** development database (host `127.0.0.1` / `localhost`). If unsure, skip this step and say so.

```powershell
php artisan migrate:status
php artisan migrate --pretend
```

Expected: the pending list contains `2026_11_19_000000_add_org_consent_to_domain_order_items`, and `--pretend` prints two `alter table domain_order_items add org_consent_choice varchar(20) null` / `add org_consent_responded_at timestamp null` statements. If other pending migrations are listed, report them and **do not run `migrate`**; ask the user first. If only this one is pending:

```powershell
php artisan migrate
php artisan migrate:status | Select-String "org_consent"
```

Expected: the line shows `Yes` / `Ran`.

Optional column check (use the first form that works):

```powershell
php artisan tinker --execute="echo json_encode([\Schema::hasColumn('domain_order_items','org_consent_choice'), \Schema::hasColumn('domain_order_items','org_consent_responded_at')]);"
```

Expected: `[true,true]`. (If `--execute` is not supported by this Tinker version, say so and skip.)

## 7. Activity probe command

```powershell
php artisan domain:activity-probe --order=1
```

Expected without real ResellerClub credentials: `ResellerClub is not configured (check services.resellerclub in .env).` and exit code 1.

With real credentials in `.env` (`RESELLERCLUB_ENABLED=true`), use a domain the **user** gives you:

```powershell
php artisan domain:activity-probe example.com
```

Expected shape:

```
ResellerClub order id: 127547539
/actions/search-current.json     OK, 0 row(s)          <- or UNREADABLE
/actions/search-archived.json    OK, 5 row(s)
Latest Activity would show: 5 action(s) in total
```

Report the whole output. If `search-current` says `UNREADABLE`, also paste the last lines of `storage\logs\laravel.log` containing `ResellerClub request failed` or `ResellerClub activity` (remove the api-key if it appears).

---

## 8. Browser acceptance checklist (for the human owner, with a real paid domain)

Open the site, log in as a customer who owns an active domain, go **My Store → Domains → Manage**.

**A. Organization Name Consent**
1. Domain never answered: the grey **Organization Name Consent:** box is shown with **Yes, I agree** and **Cancel**.
2. Click **Yes, I agree**. Expected: page reloads; a green message "Organization publication consent accepted." at the top; the box is still visible but both buttons are greyed and cannot be clicked.
3. Wait 5 seconds. Expected: the box fades out and disappears.
4. Refresh the page. Expected: the box is **gone** and stays gone on every later visit (also after logging out/in).
5. A different domain never answered still shows its own box. Test **Cancel** there: message "Organization publication consent cancelled.", box hides after 5 s, gone on refresh.
6. Failure case (optional; e.g. temporarily disable internet): red error at the top and the box stays.
7. Database: `domain_order_items.org_consent_choice` = `accepted` / `declined` and `org_consent_responded_at` filled for the answered domains.

**B. Latest Activity**
1. Open **Manage** for a domain whose last action is **older than 24 hours**. Expected: LATEST ACTIVITY lists the 3 newest actions of all time (not "Activity is temporarily unavailable").
2. Click **View All**. Expected: every action since registration, grouped by date.
3. Make a nameserver change, then open Manage again. Expected: the new action is at the top immediately.
4. A domain that truly has no actions shows "No activity".

---

## 9. Report format

Reply with a table, then details only for failures:

| Step | Result (PASS/FAIL/SKIPPED) | Evidence (command output, trimmed) |
|---|---|---|
| 2 Environment | | |
| 3 Lint | | |
| 4 Blade/routes/command | | |
| 5 Tests: Unit consent | | |
| 5 Tests: Unit activity | | |
| 5 Tests: Feature activity | | |
| 5 Tests: MyStorePages | | |
| 5 Full suite | | |
| 6 Migration | | |
| 7 Probe command | | |

End with: total tests run, total failures, and the **exact** text of every failure message.
