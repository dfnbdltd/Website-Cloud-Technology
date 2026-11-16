<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Services\DomainTransferSyncService;
use Illuminate\Console\Command;

/**
 * Asks ResellerClub how every submitted domain transfer is doing and updates
 * our orders (see DomainTransferSyncService). Scheduled in App\Console\Kernel.
 *
 *   php artisan transfers:sync                  all pending transfers
 *   php artisan transfers:sync --order=CT-2026… one order, by reference
 */
class SyncDomainTransfers extends Command
{
    protected $signature = 'transfers:sync {--order= : Only the order with this reference} {--limit=100 : Most transfers to check in one run}';

    protected $description = 'Sync submitted domain transfers with ResellerClub';

    public function handle(DomainTransferSyncService $sync)
    {
        if ($reference = $this->option('order')) {
            $order = DomainOrder::where('reference', $reference)->first();
            if (!$order) {
                $this->error($this->stamp() . 'No order with reference ' . $reference . '.');

                return 1;
            }

            $counts = $sync->syncItems($sync->pendingItems()->where('domain_order_id', $order->id));
        } else {
            $counts = $sync->syncAllPending((int) $this->option('limit'));
        }

        if (!$counts) {
            $this->info($this->stamp() . 'No submitted transfers are waiting on ResellerClub.');

            return 0;
        }

        // One line per run, so the scheduler's log (storage/logs/transfers-sync.log) reads as a history.
        $this->line($this->stamp() . collect($counts)->map(function ($count, $outcome) {
            return $outcome . '=' . $count;
        })->implode(' '));

        return 0;
    }

    private function stamp()
    {
        return '[' . now()->toDateTimeString() . '] transfers:sync ';
    }
}
