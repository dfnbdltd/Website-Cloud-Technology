# Test instructions for the testing agent

You are testing a Laravel 6 project (PHP 8.x, MySQL) that resells domains through ResellerClub.
A new feature automatically syncs domain TRANSFERS from ResellerClub and lets a finished transfer be
managed like a registered domain. Follow the steps IN ORDER. Do exactly what each step says.

## Rules
1. Do NOT edit any file in `app/`, `routes/`, `resources/`, `config/`, `database/` or `tests/`. You only run commands and report.
2. If a test fails, do NOT change the test or the code to make it pass. Copy the exact error into your report.
3. Never print or copy secrets (the values in `.env`, API keys, EPP/auth codes). Say "present" or "missing" only.
4. If a step cannot run (missing tool, no database, no network), write `BLOCKED` plus the exact error and continue.
5. Report every step as PASS, FAIL or BLOCKED with the command you ran and the first 15 lines of output.

## Step 0 - Environment
    php -v                       # must be 7.2.5 or newer
    composer --version
    ls vendor/autoload.php       # if missing: composer install --no-interaction
    ls .env                      # if missing: cp .env.example .env && php artisan key:generate
Use a TEST database, never production. In `.env` set DB_* to an empty test database, `RESELLERCLUB_ENABLED=false`.

## Step 1 - Syntax check of every changed file (must print "No syntax errors" 18 times)
    for f in app/Console/Commands/SyncDomainTransfers.php app/Console/Kernel.php \
      app/Http/Controllers/AdminController.php app/Http/Controllers/MyStoreController.php \
      app/Models/DomainOrderItem.php app/Services/DomainTransferService.php \
      app/Services/DomainTransferSyncService.php app/Services/ResellerClubClient.php \
      app/Support/DomainTransferProgress.php app/Support/DomainTransferStatus.php \
      app/Support/ResellerClubOrderId.php app/Support/PendingTransferList.php app/Support/StoreInventory.php config/services.php routes/web.php \
      tests/Feature/DomainTransferSyncTest.php tests/Unit/DomainTransferProgressTest.php \
      database/migrations/2026_11_16_000000_add_transfer_sync_fields_to_domain_order_items.php; do php -l "$f"; done

## Step 2 - Automated tests
    vendor/bin/phpunit --filter DomainTransferProgressTest
    vendor/bin/phpunit --filter DomainTransferSyncTest
    vendor/bin/phpunit --filter "DomainTransferWorkflowTest|MyStorePagesTest|DomainActivityTest"
    vendor/bin/phpunit
Expected: all green. In your report list the number of tests and assertions, and the name of every failing test with its error message.
(The feature tests build their own tables with RefreshDatabase; they do not need the SQL file.)

## Step 3 - SQL file
Only on the empty TEST database after running `php artisan migrate`:
open `database/transfer_sync.sql`, run STEP 0 to STEP 3 with the mysql client.
Expected: STEP 0 returns 2 rows if the migration already added the columns (then skip STEP 1); STEP 3 runs without error.
Confirm: `SHOW COLUMNS FROM domain_order_items;` lists `expires_at` and `transfer_completed_at`.

## Step 4 - Command and scheduler
    php artisan list | grep transfers        # expect: transfers:sync
    php artisan transfers:sync               # expect: "No submitted transfers are waiting on ResellerClub."
    php artisan transfers:sync --order=NOPE  # expect: "No order with reference NOPE." and exit code 1
Then insert one fake pending transfer (test DB only) and run the command again with ResellerClub disabled:
    php artisan tinker
    >>> $u = App\User::first() ?: App\User::create(['name'=>'T','email'=>'t@example.test','password'=>bcrypt('x'),'email_verified_at'=>now()]);
    >>> $o = App\Models\DomainOrder::create(['reference'=>'AGENT-1','user_id'=>$u->id,'total'=>1500,'status'=>'transfer_pending','payment_method'=>'bkash','payment_status'=>'paid','provider'=>'bkash','customer_name'=>'T','customer_phone'=>'0170','customer_email'=>'t@example.test']);
    >>> $o->items()->create(['domain'=>'agent-test.com','tld'=>'com','years'=>1,'price'=>1500,'renewal_price'=>1400,'order_type'=>'transfer','registration_status'=>'transfer_submitted']);
    >>> exit
    php artisan transfers:sync
Expected output: a line ending in `unavailable=1` (ResellerClub is disabled, so NOTHING may change).
Verify with tinker that the item is still `transfer_submitted` and the order still `transfer_pending`.

## Step 5 - Pages (needs `php artisan serve`, log in as the test user in a browser, or use the feature tests as proof)
For the fake order above, open `/mystore/domains`. Expected:
- a card "agent-test.com" with the label "Pending" and a "Check status" button (no Manage button)
- text: "The request was accepted by the registrar"
Now set the item to transferred in tinker:
    >>> App\Models\DomainOrderItem::where('domain','agent-test.com')->update(['registration_status'=>'transferred','transfer_completed_at'=>now()]);
    >>> App\Models\DomainOrder::where('reference','AGENT-1')->update(['status'=>'active']);
Reload `/mystore/domains`. Expected: label "Success", a "Manage" button, facts TRANSFERRED / EXPIRES / RENEWAL.
Click Manage (`/mystore/domains/{item id}`). Expected on the page: "DOMAIN TRANSFER", "Organization Name Consent",
"Yes, I agree" and "Cancel" buttons, "NAMESERVERS & DNS" with "Edit Nameservers", "LATEST ACTIVITY", "View All".
(Nameserver/consent/activity values come from ResellerClub, so with it disabled they show the empty states. That is expected.)

## Step 6 - Live ResellerClub check (ONLY if the owner gave you working credentials in `.env`; otherwise write BLOCKED)
Set `RESELLERCLUB_ENABLED=true`. Then, with the real order id 127547539 (domain netsgoods.com), run:
    php artisan tinker
    >>> $c = app(App\Services\ResellerClubClient::class);
    >>> $d = $c->domainDetails('127547539'); echo json_encode(array_intersect_key((array)$d, array_flip(['currentstatus','endtime','creationtime','domainname','orderid'])));
    >>> $a = $c->domainActions('127547539'); foreach ((array)$a as $r) { echo $r['type'].' | '.$r['title'].' | '.$r['status']." | ".$r['state']."\n"; }
    >>> print_r(App\Support\DomainTransferProgress::evaluate($a, $d));
    >>> var_export($c->pendingTransfers('netsgoods.com'));
Expected: `currentstatus` is `Active`; one row's title starts with "Transfer of netsgoods.com from"; evaluate() returns state `completed`.
`pendingTransfers('netsgoods.com')` must return an array whose `transfers` is EMPTY (the transfer is finished, so it is not pending).
`var_export` printing `NULL` means the v2 endpoint could not be read: copy the last "ResellerClub v2 request failed" line from storage/logs/laravel.log.
IMPORTANT for the report: copy the exact `currentstatus` value and the exact `type` of the Transfer row. The owner needs them.

## Step 7 - Report
Give one table: Step | PASS/FAIL/BLOCKED | evidence. Then a short list "Problems found" (exact errors) and "Things I could not check".
