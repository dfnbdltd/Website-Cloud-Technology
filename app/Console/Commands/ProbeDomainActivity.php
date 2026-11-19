<?php

namespace App\Console\Commands;

use App\Models\DomainOrderItem;
use App\Services\ResellerClubClient;
use Illuminate\Console\Command;

/**
 * Shows, live, what ResellerClub returns for one domain's activity, endpoint by
 * endpoint. Use it to see why Latest Activity is empty or unavailable.
 *
 *   php artisan domain:activity-probe example.com
 *   php artisan domain:activity-probe --order=127547539
 *
 * An endpoint that cannot be read is explained in storage/logs/laravel.log
 * ("ResellerClub request failed" / "ResellerClub activity lookup failed").
 */
class ProbeDomainActivity extends Command
{
    protected $signature = 'domain:activity-probe {domain? : Domain name as stored on the order} {--order= : ResellerClub order ID (skips the lookup)}';

    protected $description = 'Read a domain\'s ResellerClub activity live, per endpoint (current vs archived)';

    public function handle(ResellerClubClient $client)
    {
        $orderId = trim((string) $this->option('order'));

        if ($orderId === '') {
            $domain = strtolower(trim((string) $this->argument('domain')));
            if ($domain === '') {
                $this->error('Give a domain name or --order=<ResellerClub order id>.');

                return 1;
            }

            $item = DomainOrderItem::where('domain', $domain)->first();
            $orderId = $item && trim((string) $item->reseller_order_id) !== ''
                ? trim((string) $item->reseller_order_id)
                : (string) $client->domainOrderId($domain);
        }

        if ($orderId === '' || !ctype_digit($orderId)) {
            $this->error('No ResellerClub order id could be found.');

            return 1;
        }

        $this->line('ResellerClub order id: ' . $orderId);

        $report = $client->probeDomainActions($orderId);
        if ($report === null) {
            $this->error('ResellerClub is not configured (check services.resellerclub in .env).');

            return 1;
        }

        foreach ($report as $path => $count) {
            if ($count === null) {
                $this->warn(sprintf('%-32s UNREADABLE (see storage/logs/laravel.log)', $path));
            } else {
                $this->info(sprintf('%-32s OK, %d row(s)', $path, $count));
            }
        }

        $actions = $client->domainActions($orderId);
        $this->line('Latest Activity would show: ' . ($actions === null ? 'UNAVAILABLE' : count($actions) . ' action(s) in total'));

        return $actions === null ? 2 : 0;
    }
}
