<?php

namespace App\Services;

/**
 * The domain availability search, shared by the Domain Registration page
 * (DomainController::search) and the hosting "Register a New Domain" box
 * (HostingCheckoutController::searchDomains), so both always show the same
 * names, prices and available / registered answers.
 */
class DomainSearchService
{
    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    /**
     * @param  string  $domain  "xyz" (every catalog extension) or "xyz.com" (just that one)
     * @return array  one row per extension: domain, tld, available (true|false|null = lookup failed),
     *                price, renewal, year_prices, classkey, source
     */
    public function search($domain)
    {
        $domain = strtolower(trim((string) $domain));
        $parts = explode('.', $domain, 2);
        $name = $parts[0];
        $requestedTld = isset($parts[1]) ? $parts[1] : null;

        $testDomains = config('services.test_domains', []);
        if (isset($testDomains[$domain])) {
            return [[
                'domain' => $domain,
                'tld' => 'test',
                'available' => true,
                'price' => $testDomains[$domain]['price'],
                'renewal' => $testDomains[$domain]['renewal'],
                'year_prices' => [1 => (float) $testDomains[$domain]['price']],
                'classkey' => 'test',
                'source' => 'test',
            ]];
        }

        $catalog = $this->resellerClub->catalog();

        // Search all catalog TLDs, or just the requested one when it is in the catalog.
        if ($requestedTld && isset($catalog[$requestedTld])) {
            $tldList = [$requestedTld => $catalog[$requestedTld]];
        } else {
            $tldList = $catalog;
        }

        $results = [];
        foreach ($tldList as $tld => $plan) {
            $remote = $this->resellerClub->checkAvailability($name, $tld);
            $entry = is_array($remote) ? ($remote[$name . '.' . $tld] ?? null) : null;
            $status = is_array($entry) ? strtolower($entry['status'] ?? '') : '';
            // null = the lookup failed (API error / IP not whitelisted), not "taken".
            $available = $status === '' ? null : in_array($status, ['available', 'notinuse'], true);

            // If the classkey differs from the catalog, pull its price live.
            $price = $plan['price'];
            $renewal = $plan['renewal'];
            $yearPrices = $plan['year_prices'];
            if (!empty($entry['classkey']) && $entry['classkey'] !== $plan['classkey']) {
                $pricing = $this->resellerClub->pricesForClasskey($entry['classkey'], [
                    'add' => $price,
                    'renew' => $renewal,
                ]);
                $price = $pricing['price'];
                $renewal = $pricing['renewal'];
                $yearPrices = $pricing['year_prices'];
            }

            $results[] = [
                'domain' => $name . '.' . $tld,
                'tld' => $tld,
                'available' => $available,
                'price' => $price,
                'renewal' => $renewal,
                'year_prices' => $yearPrices,
                'classkey' => $plan['classkey'],
                'source' => $status ? 'resellerclub' : 'catalog',
            ];
        }

        return $results;
    }
}
