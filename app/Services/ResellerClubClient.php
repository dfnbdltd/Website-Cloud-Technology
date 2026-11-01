<?php

namespace App\Services;

class ResellerClubClient
{
    private $config;

    /**
     * Fallback catalog used when the live pricing API is unreachable.
     * Prices in BDT for 1 year.
     */
    private $fallback = [
        'domcno' => ['tld' => 'com', 'add' => 1214, 'renew' => 1399, 'popular' => true],
        'dotnet' => ['tld' => 'net', 'add' => 2213, 'renew' => 2804],
        'dotxyz' => ['tld' => 'xyz', 'add' => 2494, 'renew' => 3099],
        'dottop' => ['tld' => 'top', 'add' => 1342, 'renew' => 1593],
        'doticu' => ['tld' => 'icu', 'add' => 2641, 'renew' => 2804],
        'dotlive' => ['tld' => 'live', 'add' => 3985, 'renew' => 4723],
        'dotclub' => ['tld' => 'club', 'add' => 3409, 'renew' => 4044],
        'dotwebsite' => ['tld' => 'website', 'add' => 4427, 'renew' => 4427],
    ];

    public function __construct()
    {
        $this->config = config('services.resellerclub');
    }

    public function isConfigured()
    {
        return $this->config['enabled'] && $this->config['auth_userid'] && $this->config['api_key'];
    }

    /**
     * Live customer pricing for all products, keyed by ResellerClub classkey.
     * Cached for 6 hours. Returns [] when unavailable.
     */
    public function pricing()
    {
        if (!$this->isConfigured()) {
            return [];
        }

        return cache()->remember('resellerclub.pricing', now()->addHours(6), function () {
            $response = $this->request('/products/customer-price.json');

            if (!is_array($response) || isset($response['status'])) {
                return [];
            }

            return $response;
        });
    }

    /**
     * Public catalog: one row per TLD we want to show, using live prices.
     */
    public function catalog(array $classkeys = ['domcno', 'dotnet', 'dotxyz', 'dottop', 'doticu', 'dotlive', 'dotclub', 'dotwebsite'])
    {
        $pricing = $this->pricing();
        $catalog = [];

        foreach ($classkeys as $classkey) {
            $live = $pricing[$classkey] ?? null;
            $fallback = $this->fallback[$classkey] ?? null;
            if (!$live && !$fallback) {
                continue;
            }

            $tld = $fallback['tld'] ?? preg_replace('/^dot/', '', $classkey);
            $prices = $this->priceOptions($live, $fallback);
            $catalog[$tld] = [
                'classkey' => $classkey,
                'price' => $prices['price'],
                'renewal' => $prices['renewal'],
                'year_prices' => $prices['year_prices'],
                'popular' => isset($fallback['popular']),
            ];
        }

        return $catalog;
    }

    /**
     * Registration totals for each supported term, keyed by number of years.
     */
    public function pricesForClasskey($classkey, array $fallback = [])
    {
        return $this->priceOptions($this->pricing()[$classkey] ?? null, $fallback);
    }

    public function checkAvailability($domain, $tld)
    {
        if (!$this->isConfigured()) {
            return null;
        }

        return $this->request('/domains/available.json', [
            'domain-name' => $domain,
            'tlds' => $tld,
            'no-of-years' => 1,
        ]);
    }

    public function registerDomain($domain, $years = 1)
    {
        if (!$this->isConfigured() || !$this->config['customer_id']) {
            return null;
        }

        return $this->request('/domains/register.json', [
            'domain-name' => $domain,
            'years' => $years,
            'customer-id' => $this->config['customer_id'],
            'invoice-option' => 'NoInvoice',
            'protect-privacy' => 'false',
        ]);
    }

    private function priceOptions($live, array $fallback = [])
    {
        $liveFirstYearPrice = $live['addnewdomain']['1'] ?? null;
        $liveRenewalPrice = $live['renewdomain']['1'] ?? null;
        $firstYearPrice = $liveFirstYearPrice !== null
            ? $this->convertLivePriceToBdt($liveFirstYearPrice)
            : (float) ($fallback['add'] ?? 0);
        $renewalPrice = $liveRenewalPrice !== null
            ? $this->convertLivePriceToBdt($liveRenewalPrice)
            : (float) ($fallback['renew'] ?? $firstYearPrice);
        $yearPrices = [];

        for ($years = 1; $years <= 10; $years++) {
            $liveYearPrice = $live['addnewdomain'][(string) $years] ?? null;
            $convertedLiveYearPrice = $liveYearPrice !== null
                ? $this->convertLivePriceToBdt($liveYearPrice)
                : null;

            // Some reseller accounts return the one-year price for every term.
            if ($years > 1 && $convertedLiveYearPrice === $firstYearPrice) {
                $convertedLiveYearPrice = null;
            }

            $yearPrices[$years] = $convertedLiveYearPrice !== null
                ? $convertedLiveYearPrice
                : ($firstYearPrice + ($renewalPrice * ($years - 1)));
        }

        return [
            'price' => $firstYearPrice,
            'renewal' => $renewalPrice,
            'year_prices' => $yearPrices,
        ];
    }

    private function convertLivePriceToBdt($price)
    {
        $currency = strtoupper((string) ($this->config['price_currency'] ?? 'USD'));
        $amount = (float) $price;

        if ($currency === 'BDT') {
            return $amount;
        }

        if ($currency !== 'USD') {
            throw new \InvalidArgumentException('Unsupported ResellerClub price currency: ' . $currency);
        }

        $rate = app(ExchangeRateService::class)->usdToBdt()['rate'];

        return (float) round($amount * $rate);
    }

    private function request($path, array $parameters = [])
    {
        $parameters['auth-userid'] = $this->config['auth_userid'];
        $parameters['api-key'] = $this->config['api_key'];
        $url = rtrim($this->config['base_url'], '/') . $path . '?' . http_build_query($parameters);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $error) {
            \Log::warning('ResellerClub request failed', ['path' => $path, 'error' => $error]);

            return null;
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded) || (isset($decoded['status']) && strtoupper($decoded['status']) === 'ERROR')) {
            \Log::warning('ResellerClub API error', ['path' => $path, 'response' => substr($body, 0, 500)]);
        }

        return is_array($decoded) ? $decoded : null;
    }
}
