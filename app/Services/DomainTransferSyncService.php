<?php

namespace App\Services;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Support\DomainActivity;
use App\Support\DomainTransferProgress;
use App\Support\PendingTransferList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Brings our record of a submitted transfer in line with ResellerClub.
 *
 * ResellerClub is the source of truth: a transfer is "submitted" the moment its
 * API accepts the request, but it only finishes days later when the losing
 * registrar releases the domain. Nothing pushes that back to us, so we ask:
 *
 *   - the `transfers:sync` command, on the scheduler (the reliable path);
 *   - the customer's My Domains page, throttled per customer (a safety net
 *     for a server whose cron is not running yet);
 *   - the "Check status" buttons (customer and admin).
 *
 * An unreadable ResellerClub is never read as "no change needed" or "failed":
 * the item simply stays as it is and is asked again next time.
 *
 * Item status: transfer_submitted -> transferred | transfer_failed
 * Order status: transfer_pending  -> active      | transfer_failed
 */
class DomainTransferSyncService
{
    const OUTCOME_COMPLETED = 'completed';
    const OUTCOME_FAILED = 'failed';
    const OUTCOME_PENDING = 'pending';
    const OUTCOME_UNAVAILABLE = 'unavailable';
    const OUTCOME_SKIPPED = 'skipped';

    /** A customer page view re-asks ResellerClub at most this often per customer. */
    const VIEW_THROTTLE_SECONDS = 120;

    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    /**
     * Submitted transfers still waiting on ResellerClub, oldest first.
     *
     * @param  int|null  $userId
     * @param  int|null  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pendingItems($userId = null, $limit = null)
    {
        $query = DomainOrderItem::with('order')
            ->where('order_type', 'transfer')
            ->where('registration_status', 'transfer_submitted')
            ->whereHas('order', function ($order) use ($userId) {
                $order->where('payment_status', 'paid');
                if ($userId !== null) {
                    $order->where('user_id', $userId);
                }
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        return $query->get();
    }

    /**
     * Every pending transfer — what the scheduler runs.
     *
     * @return array  outcome => count
     */
    public function syncAllPending($limit = 100)
    {
        return $this->syncItems($this->pendingItems(null, $limit));
    }

    /**
     * One customer's pending transfers, at most once per throttle window unless forced.
     */
    public function syncPendingForUser($userId, $force = false)
    {
        if (!config('services.resellerclub.transfer_sync_on_view', true) && !$force) {
            return [];
        }

        $items = $this->pendingItems((int) $userId);
        if ($items->isEmpty()) {
            return [];
        }

        // Cache::add is atomic: of two simultaneous page views only one asks ResellerClub.
        if (!$force && !Cache::add('transfer-sync.user.' . (int) $userId, 1, self::VIEW_THROTTLE_SECONDS)) {
            return [];
        }

        return $this->syncItems($items);
    }

    /**
     * Transferred domains whose expiry is not stored yet (an admin marked them complete by hand,
     * or they were promoted by the SQL step): read it once from ResellerClub and keep it.
     * At most 5 per call and once per 10 minutes per customer.
     *
     * @return int  how many expiry dates were filled
     */
    public function backfillExpiry($userId, $limit = 5)
    {
        if (!$this->resellerClub->isConfigured() || !Cache::add('transfer-expiry-backfill.user.' . (int) $userId, 1, 600)) {
            return 0;
        }

        $items = DomainOrderItem::with('order')
            ->where('order_type', 'transfer')
            ->where('registration_status', DomainOrderItem::STATUS_TRANSFERRED)
            ->whereNull('expires_at')
            ->whereHas('order', function ($order) use ($userId) {
                $order->where('user_id', (int) $userId)->where('payment_status', 'paid');
            })
            ->orderBy('id')
            ->limit((int) $limit)
            ->get();

        $filled = 0;
        foreach ($items as $item) {
            if (array_key_exists($item->domain, (array) config('services.test_domains', []))) {
                continue;
            }

            $orderId = $this->resellerOrderId($item);
            $details = $orderId === null ? null : $this->resellerClub->domainDetails($orderId);
            $endTime = is_array($details) && isset($details['endtime']) ? DomainActivity::timestamp($details['endtime']) : null;
            if ($endTime) {
                $item->update(['expires_at' => Carbon::createFromTimestamp($endTime)]);
                $filled++;
            }
        }

        return $filled;
    }

    /**
     * @param  \Illuminate\Support\Collection  $items
     * @return array  outcome => count
     */
    public function syncItems($items)
    {
        $counts = [];

        // ONE ResellerClub call tells us which of all these transfers are still in
        // progress, instead of three calls per transfer. With nothing pending
        // locally there is no call at all.
        $index = $items->isEmpty() || !$this->resellerClub->isConfigured()
            ? null
            : $this->pendingIndex($items);

        foreach ($items as $item) {
            $outcome = $this->syncItem($item, $index);
            $counts[$outcome] = ($counts[$outcome] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * ResellerClub's list of transfers still in progress (v2 /domain-transfers/pending),
     * keyed by lowercase domain.
     *
     * One item: asked by domain name. Several: the whole list is paged once (at most
     * 20 pages of 100) and matched in memory.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @return array|null  null when the list could not be read; callers then fall back to
     *                     the order's own actions and details
     */
    private function pendingIndex($items)
    {
        $domains = $items->pluck('domain')->map(function ($domain) {
            return strtolower(trim($domain));
        })->unique()->values();

        if ($domains->count() === 1) {
            $result = $this->resellerClub->pendingTransfers($domains->first());

            return $result === null ? null : $result['transfers'];
        }

        $index = [];
        for ($page = 1; $page <= 20; $page++) {
            $result = $this->resellerClub->pendingTransfers(null, $page, 100);
            if ($result === null) {
                return null;
            }

            $index = array_merge($index, $result['transfers']);
            if ($result['count'] < 100 || count($index) >= $result['total']) {
                break;
            }
        }

        return $index;
    }

    /**
     * @param  array|null|false  $pendingIndex  false: look this one item up; null: the pending list was
     *                                          unreadable (use the order's actions only); array: the list
     * @return string  one of the OUTCOME_* constants
     */
    public function syncItem(DomainOrderItem $item, $pendingIndex = false)
    {
        $item->loadMissing('order');
        $order = $item->order;

        if (
            !$order
            || $order->payment_status !== 'paid'
            || $item->order_type !== 'transfer'
            || $item->registration_status !== 'transfer_submitted'
            || array_key_exists($item->domain, (array) config('services.test_domains', []))
        ) {
            return self::OUTCOME_SKIPPED;
        }

        if (!$this->resellerClub->isConfigured()) {
            return self::OUTCOME_UNAVAILABLE;
        }

        if ($pendingIndex === false) {
            $pendingIndex = $this->pendingIndex(collect([$item]));
        }

        // Signal 1 (v2 pending list): a transfer ResellerClub still lists as in progress is
        // not finished. Its order ID is kept so the order's own history can be read later.
        $listed = is_array($pendingIndex) ? ($pendingIndex[strtolower(trim($item->domain))] ?? null) : null;
        if ($listed !== null && !PendingTransferList::looksFailed($listed['action_status'])) {
            if ($listed['order_id'] !== null && trim((string) $item->reseller_order_id) === '') {
                $item->update(['reseller_order_id' => $listed['order_id']]);
            }

            Log::info('Domain transfer sync evaluated', [
                'order' => $order->reference,
                'domain' => $item->domain,
                'state' => DomainTransferProgress::PENDING,
                'reason' => 'listed by ResellerClub as pending (' . $listed['action_status'] . ')',
            ]);
            $this->warnIfStuck($item, $order);

            return self::OUTCOME_PENDING;
        }

        // Signal 2: not on the pending list (or the list was unreadable) — read the order itself.
        $orderId = $this->resellerOrderId($item);
        if ($orderId === null) {
            // ResellerClub has no order for this domain (yet): the transfer is still being set up.
            return self::OUTCOME_PENDING;
        }

        // Always read fresh: a 60-second old "pending" must not hide a finished transfer.
        ResellerClubClient::forgetDomainActions($orderId);
        $actions = $this->resellerClub->domainActions($orderId);
        $details = $this->resellerClub->domainDetails($orderId);

        if ($actions === null && $details === null) {
            Log::warning('Domain transfer sync could not read ResellerClub', [
                'order' => $order->reference,
                'domain' => $item->domain,
            ]);

            return self::OUTCOME_UNAVAILABLE;
        }

        $progress = DomainTransferProgress::evaluate($actions, $details);
        Log::info('Domain transfer sync evaluated', [
            'order' => $order->reference,
            'domain' => $item->domain,
            'state' => $progress['state'],
            'reason' => $progress['reason'],
            'pending_list' => is_array($pendingIndex) ? 'not listed' : 'unreadable',
        ]);

        if ($progress['state'] === DomainTransferProgress::PENDING) {
            $this->warnIfStuck($item, $order);
        }

        if ($progress['state'] === DomainTransferProgress::COMPLETED) {
            return $this->markCompleted($item, $progress) ? self::OUTCOME_COMPLETED : self::OUTCOME_SKIPPED;
        }

        if ($progress['state'] === DomainTransferProgress::FAILED) {
            return $this->markFailed($item) ? self::OUTCOME_FAILED : self::OUTCOME_SKIPPED;
        }

        return self::OUTCOME_PENDING;
    }

    /**
     * Transfers normally finish in 5–7 days. One still waiting after two weeks usually
     * means the admin contact never approved the transfer e-mail, or the old registrar
     * refused: leave a clear line in the log for an admin to act on.
     */
    private function warnIfStuck(DomainOrderItem $item, DomainOrder $order)
    {
        if ($order->created_at && $order->created_at->lt(now()->subDays(14))) {
            Log::warning('Domain transfer has been pending for more than 14 days', [
                'order' => $order->reference,
                'domain' => $item->domain,
                'since' => $order->created_at->toDateString(),
            ]);
        }
    }

    /**
     * The ResellerClub order of this item: stored once, resolved on first use.
     *
     * @return string|null
     */
    private function resellerOrderId(DomainOrderItem $item)
    {
        $stored = trim((string) $item->reseller_order_id);
        if ($stored !== '' && ctype_digit($stored)) {
            return $stored;
        }

        $orderId = $this->resellerClub->domainOrderId($item->domain);
        if ($orderId !== null) {
            $item->update(['reseller_order_id' => $orderId]);
        }

        return $orderId;
    }

    private function markCompleted(DomainOrderItem $item, array $progress)
    {
        return DB::transaction(function () use ($item, $progress) {
            $locked = DomainOrderItem::whereKey($item->id)->lockForUpdate()->first();
            if (!$locked || $locked->registration_status !== 'transfer_submitted') {
                return false;
            }

            $locked->update([
                'registration_status' => DomainOrderItem::STATUS_TRANSFERRED,
                // The EPP code is single-use and no longer needed.
                'transfer_auth_code' => null,
                'expires_at' => $progress['expires_at'] ? Carbon::createFromTimestamp($progress['expires_at']) : $locked->expires_at,
                'transfer_completed_at' => $progress['completed_at'] ? Carbon::createFromTimestamp($progress['completed_at']) : now(),
            ]);

            $this->refreshOrderStatus($locked->domain_order_id);

            Log::info('Domain transfer completed at ResellerClub', [
                'item_id' => $locked->id,
                'domain' => $locked->domain,
            ]);

            return true;
        });
    }

    private function markFailed(DomainOrderItem $item)
    {
        return DB::transaction(function () use ($item) {
            $locked = DomainOrderItem::whereKey($item->id)->lockForUpdate()->first();
            if (!$locked || $locked->registration_status !== 'transfer_submitted') {
                return false;
            }

            $locked->update(['registration_status' => 'transfer_failed']);
            $this->refreshOrderStatus($locked->domain_order_id);

            Log::warning('Domain transfer failed at ResellerClub', [
                'item_id' => $locked->id,
                'domain' => $locked->domain,
            ]);

            return true;
        });
    }

    /**
     * The order follows its transfer items: any failure -> transfer_failed (an
     * admin must act), otherwise active once every item is transferred, otherwise
     * it keeps waiting as transfer_pending.
     */
    private function refreshOrderStatus($orderId)
    {
        $order = DomainOrder::whereKey($orderId)->lockForUpdate()->first();
        if (!$order) {
            return;
        }

        $statuses = $order->items()->where('order_type', 'transfer')->pluck('registration_status')->all();
        if (!$statuses) {
            return;
        }

        if (in_array('transfer_failed', $statuses, true)) {
            $next = 'transfer_failed';
        } elseif (count(array_diff($statuses, [DomainOrderItem::STATUS_TRANSFERRED])) === 0) {
            $next = 'active';
        } else {
            $next = $order->status;
        }

        if ($next !== $order->status) {
            $order->update(['status' => $next]);
        }
    }
}
