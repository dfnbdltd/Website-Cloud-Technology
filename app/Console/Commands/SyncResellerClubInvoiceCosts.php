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

        $updated = 0;
        $unavailable = 0;

        DomainOrder::with('items')->chunkById(100, function ($orders) use ($resellerClub, &$updated, &$unavailable) {
            foreach ($orders as $order) {
                $invoice = Invoice::where('source_type', DomainOrder::class)
                    ->where('source_id', $order->id)
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

                    $description = 'Domain ' . ($item->order_type === 'transfer' ? 'transfer' : 'registration') . ' — ' . $item->domain;

                    DB::transaction(function () use ($item, $invoice, $description, $cost) {
                        $item->forceFill(['purchase_price' => $cost])->save();

                        if ($invoice) {
                            $invoice->items()->where('description', $description)->update([
                                'purchase_price' => $cost,
                            ]);
                        }
                    });

                    $updated++;
                }
            }
        });

        $this->info("Updated {$updated} domain cost snapshot(s) using current reseller pricing; {$unavailable} cost(s) were unavailable.");
        $this->warn('These are current prices, not historical purchase rates.');

        return 0;
    }
}
