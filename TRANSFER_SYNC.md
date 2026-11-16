# Domain transfer sync (ResellerClub is the source of truth)

A transfer is "submitted" when ResellerClub accepts the API call, but it finishes days later
when the old registrar releases the domain (or the admin contact approves the transfer email).
We ask ResellerClub instead of waiting for an admin to click Success.

## 1. Database (no `php artisan migrate` needed)
Run `database/transfer_sync.sql` in phpMyAdmin, steps in order. It adds two nullable columns to
`domain_order_items` (`expires_at`, `transfer_completed_at`) and promotes transfers an admin already
marked Success to `transferred`. The status values are plain strings: no other schema change.

## 2. Cron job (the automatic part)
One cron entry runs Laravel's scheduler every minute; the scheduler runs `transfers:sync` every 10 minutes.

cPanel > Cron Jobs > "Once Per Minute" > Command (adjust the two paths; find PHP with `which php`
over SSH, on cPanel it is often `/usr/local/bin/php` or `/opt/cpanel/ea-php82/root/usr/bin/php`):

    * * * * * cd /home/CPANELUSER/path/to/moon && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1

Prove it works BEFORE trusting the schedule:

    cd /home/CPANELUSER/path/to/moon
    php artisan list | grep transfers          # the command must be listed
    php artisan transfers:sync                 # run it once by hand

Expected output, one line:
    [2026-11-16 12:00:01] transfers:sync completed=1            <- a transfer finished
    [2026-11-16 12:00:01] transfers:sync pending=1              <- still in progress at the registrar
    [2026-11-16 12:00:01] transfers:sync unavailable=1          <- ResellerClub unreadable / not enabled: nothing changed
    [2026-11-16 12:00:01] transfers:sync No submitted transfers are waiting on ResellerClub.

One order only:  `php artisan transfers:sync --order=CT-20261105-1FD6D3`

## 3. Logs
| File | What is in it |
|---|---|
| `storage/logs/transfers-sync.log` | one line per scheduled run (the output above). Created on the first run. |
| `storage/logs/laravel.log` | the detail: see the messages below |

    tail -f storage/logs/transfers-sync.log
    grep -E "Domain transfer (sync|completed|failed)|transfer submitted" storage/logs/laravel.log | tail -30

Messages in laravel.log:
- `ResellerClub domain transfer submitted` (has `reseller_order_id`) - written when the transfer was sent
- `Domain transfer sync evaluated` (`state` = completed/failed/pending, a `reason`, and `pending_list` = not listed/unreadable) - every check
- `Domain transfer completed at ResellerClub` / `Domain transfer failed at ResellerClub` - the status changed
- `Domain transfer sync could not read ResellerClub` - API down or wrong credentials; nothing was changed
- `ResellerClub request failed` - the underlying HTTP error

Cron itself not running? Temporarily use `>> storage/logs/cron.log 2>&1` instead of `/dev/null`
(it prints "No scheduled commands are ready to run" most minutes; switch back to /dev/null afterwards).
If the scheduler seems stuck after a crash, delete the stale lock: `php artisan cache:clear`.

## 4. How a transfer is judged
1. **ResellerClub v2 `GET /domain-transfers/pending`** (App\Support\PendingTransferList). A domain on this list is still in
   progress. ONE call covers every pending transfer of the run (paged, matched in memory); with nothing pending locally
   there is no API call at all. Its `orderId` is saved on the item.
2. Not on the list (or the list is unreadable) -> read the order itself (App\Support\DomainTransferProgress):
   - action "Transfer of <domain> from old Registrar..." Success -> completed, Failed -> failed
   - or domains/details.json `currentstatus` = Active -> completed
3. Unreadable ResellerClub -> nothing changes, asked again next run. A transfer pending for 14+ days writes a warning
   ("Domain transfer has been pending for more than 14 days") - usually the admin contact never approved the transfer e-mail.
The order ID also comes from the transfer response (`entityId` in v2, `entityid` in v1).

## 5. Statuses
item: transfer_submitted -> transferred | transfer_failed      order: transfer_pending -> active | transfer_failed
A `transferred` item has the same Manage page as a registration (nameservers, organization consent, activity).

## 5b. Is a cron job that runs all the time a good idea?
Yes - it is the standard way, and it is cheap. ResellerClub does not push transfer results to us (none of the API
documents you gave me has a webhook), so something has to ask. The one cron entry only starts Laravel's scheduler once a
minute; the scheduler runs `transfers:sync` every 10 minutes; with no pending transfer the command does one small DB query
and no API call. With pending transfers it makes ONE ResellerClub call per run. Transfers take days, so 10 minutes is plenty.
What NOT to do: a never-ending process (daemon / queue worker loop) - it needs a supervisor and gains nothing here.
Safety net on top of cron: opening My Domains and the Check status buttons also re-check (throttled).

## 6. Admin
Orders page > "Sync with ResellerClub" reads the real status. The status dropdown (Success) stays as a manual override: it also marks the transfer items `transferred` and writes
"Admin manually marked a domain transfer complete" (with the admin e-mail) to laravel.log. Use it only when ResellerClub itself
confirms the transfer, because the customer can then edit nameservers for that domain.
Optional: `RESELLERCLUB_TRANSFER_SYNC_ON_VIEW=false` in .env stops the extra re-check when a customer opens My Domains.
