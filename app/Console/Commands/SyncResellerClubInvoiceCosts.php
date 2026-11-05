<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Services\ResellerClubClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncResellerClubInvoiceCosts extends Command
{
    protected $signature = 'invoices:sync-resellerclub-costs';

    protected $description = 'Refresh saved domain invoice costs using current ResellerClub reseller prices.';

    public function handle(ResellerClubClient $resellerClub)
    {
        if (!$resellerClub->isConfigured()) {
            $this->error('ResellerClub is not configured; no invoice costs were changed.');

            return 1;
        }

        $orderItemsUpdated = 0;
        $invoiceItemsUpdated = 0;
        $invoiceItemsUnmatched = 0;
        $unavailable = 0;

        DomainOrder::with('items')->chunkById(100, function ($orders) use (
            $resellerClub,
            &$orderItemsUpdated,
            &$invoiceItemsUpdated,
            &$invoiceItemsUnmatched,
            &$unavailable
        ) {
            foreach ($orders as $order) {
                $invoice = Invoice::where('source_type', DomainOrder::class)
                    ->where('source_id', $order->id)
                    ->with('items')
                    ->first();

                foreach ($order->items as $item) {
                    $classkey = $item->tld === 'com' ? 'domcno' : 'dot' . $item->tld;
                    $cost = $item->order_type === 'transfer'
                        ? $resellerClub->liveTransferCost($item->tld)
                        : $resellerClub->liveRegistrationCost($classkey, $item->years);

                    if ($cost === null) {
                        $unavailable++;
                        continue;
                    }

                    $isTransfer = $item->order_type === 'transfer';
                    $matchingInvoiceItems = $invoice
                        ? $invoice->items->filter(function ($invoiceItem) use ($item, $isTransfer) {
                            $allowedTypes = $isTransfer
                                ? ['domain-transfer']
                                : ['domain-registration', 'domain-renewal'];
                            if (!in_array($invoiceItem->item_type, $allowedTypes, true)) {
                                return false;
                            }

                            $description = trim((string) $invoiceItem->description);
                            $domain = preg_quote($item->domain, '/');

                            return preg_match('/(?:^|[\s—-])' . $domain . '$/i', $description) === 1;
                        })
                        : collect();

                    DB::transaction(function () use (
                        $item,
                        $matchingInvoiceItems,
                        $cost,
                        &$orderItemsUpdated,
                        &$invoiceItemsUpdated,
                        &$invoiceItemsUnmatched,
                        $invoice
                    ) {
                        $item->forceFill(['purchase_price' => $cost])->save();
                        $orderItemsUpdated++;

                        if ($matchingInvoiceItems->isEmpty()) {
                            if ($invoice) {
                                $invoiceItemsUnmatched++;
                            }

                            return;
                        }

                        foreach ($matchingInvoiceItems as $invoiceItem) {
                            $invoiceItem->forceFill(['purchase_price' => $cost])->save();
                            $invoiceItemsUpdated++;
                        }
                    });
                }
            }
        });

        $this->info("Updated {$orderItemsUpdated} order-item cost snapshot(s) and {$invoiceItemsUpdated} invoice line(s) using current reseller pricing.");
        if ($invoiceItemsUnmatched > 0) {
            $this->warn("Could not match {$invoiceItemsUnmatched} domain order item(s) to an invoice line; those invoice costs were not changed.");
        }
        if ($unavailable > 0) {
            $this->warn("Reseller cost was unavailable for {$unavailable} domain order item(s); those costs were not changed.");
        }
        $this->warn('These are current prices, not historical purchase rates.');

        return 0;
    }
}
