<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Topup;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

/**
 * Safe to run multiple times: creates invoices for every domain order and
 * every approved top-up that does not have one yet. Run once after the
 * invoices migration, e.g.:
 *
 *   php artisan invoices:backfill
 */
class BackfillInvoices extends Command
{
    protected $signature = 'invoices:backfill';

    protected $description = 'Create invoices (INV-…) for existing domain orders and approved top-ups that do not have one yet.';

    public function handle(InvoiceService $invoices)
    {
        $before = Invoice::count();
        $orderCount = 0;
        $topupCount = 0;

        DomainOrder::with('items')->chunkById(100, function ($orders) use ($invoices, &$orderCount) {
            foreach ($orders as $order) {
                if ($order->items()->doesntExist()) {
                    continue;
                }
                if (!$invoices->findForSource(DomainOrder::class, $order->id)) {
                    $invoices->createForOrder($order);
                    $orderCount++;
                }
            }
        });

        Topup::where('status', 'approved')->chunkById(100, function ($topups) use ($invoices, &$topupCount) {
            foreach ($topups as $topup) {
                if (!$invoices->findForSource(Topup::class, $topup->id)) {
                    $invoices->createForTopup($topup);
                    $topupCount++;
                }
            }
        });

        $this->info("Invoices created for {$orderCount} order(s) and {$topupCount} approved top-up(s). Total invoices now: " . Invoice::count() . " (was {$before}).");

        return 0;
    }
}
