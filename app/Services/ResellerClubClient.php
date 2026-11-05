<?php

namespace App\Services;

use App\Support\ResellerClubDomainContacts;

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
        if (empty($this->config['api_key'])) {
            $this->config['api_key'] = $this->config['legacy_api_key'] ?? null;
        }
    }

    public function isConfigured()
    {
        return $this->config['enabled'] && $this->config['auth_userid'] && $this->config['api_key'];
    }

    /**
     * Live customer pricing for all products, keyed by ResellerClub classkey.
     * Cached briefly so ResellerClub price changes are reflected promptly.
     * Returns [] when unavailable.
     */
    public function pricing()
    {
        if (!$this->isConfigured()) {
            return [];
        }

        return cache()->remember('resellerclub.pricing.v2', now()->addMinutes(5), function () {
            $response = $this->request('/products/customer-price.json');

            if (!is_array($response) || isset($response['status'])) {
                return [];
            }

            return $response;
        });
    }

    /**
     * Live reseller cost prices, keyed by ResellerClub classkey.
     * Returns [] when the cost-pricing API is unavailable.
     */
    public function resellerPricing()
    {
        if (!$this->isConfigured()) {
            return [];
        }

        return cache()->remember('resellerclub.reseller_pricing.v1', now()->addMinutes(5), function () {
            $resellerId = $this->config['reseller_id'] ?: $this->config['auth_userid'];
            $response = $this->request('/products/reseller-price.json', [
                'reseller-id' => $resellerId,
            ]);

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

    /**
     * Live registration price in the configured ResellerClub pricing currency.
     * This is the customer price, so it is a conservative wallet pre-check.
     */
    public function liveRegistrationPrice($classkey, $years)
    {
        $live = $this->pricing()[$classkey] ?? null;
        $firstYear = $live['addnewdomain']['1'] ?? null;

        if ($firstYear === null || !is_numeric($firstYear)) {
            return null;
        }

        $renewal = $live['renewdomain']['1'] ?? $firstYear;
        $termPrice = $live['addnewdomain'][(string) $years] ?? null;

        if ($years > 1 && $termPrice !== null && (float) $termPrice === (float) $firstYear) {
            $termPrice = null;
        }

        if ($termPrice !== null && is_numeric($termPrice)) {
            return (float) $termPrice;
        }

        return (float) $firstYear + ((float) $renewal * ($years - 1));
    }

    public function liveTransferPrice($tld)
    {
        $tld = strtolower(trim($tld, ". \t\n\r\0\x0B"));
        $classkey = $tld === 'com' ? 'domcno' : 'dot' . $tld;
        $price = $this->pricing()[$classkey]['addtransferdomain']['1'] ?? null;

        if (!is_numeric($price)) {
            return null;
        }

        return (float) $this->convertLivePriceToBdt($price);
    }

    /**
     * Reseller cost per year for a registration term, in BDT.
     * Returns null rather than substituting the customer selling price.
     */
    public function liveRegistrationCost($classkey, $years)
    {
        $years = max(1, (int) $years);
        $live = $this->resellerPricing()[$classkey] ?? null;
        $firstYear = $live['addnewdomain']['1'] ?? null;

        if (!is_numeric($firstYear)) {
            \Log::warning('ResellerClub registration reseller cost is unavailable', [
                'classkey' => $classkey,
                'years' => $years,
            ]);

            return null;
        }

        if ($years === 1) {
            return (float) $this->convertLivePriceToBdt($firstYear);
        }

        $termPrice = $live['addnewdomain'][(string) $years] ?? null;
        if (is_numeric($termPrice) && (float) $termPrice !== (float) $firstYear) {
            return (float) $this->convertLivePriceToBdt($termPrice) / $years;
        }

        $renewalPrice = $live['renewdomain']['1'] ?? null;
        if (!is_numeric($renewalPrice)) {
            \Log::warning('ResellerClub registration reseller renewal cost is unavailable', [
                'classkey' => $classkey,
                'years' => $years,
            ]);

            return null;
        }

        $termTotal = (float) $firstYear + ((float) $renewalPrice * ($years - 1));

        return (float) $this->convertLivePriceToBdt($termTotal) / $years;
    }

    /**
     * Reseller cost for a domain transfer, in BDT.
     */
    public function liveTransferCost($tld)
    {
        $tld = strtolower(trim($tld, ". \t\n\r\0\x0B"));
        $classkey = $tld === 'com' ? 'domcno' : 'dot' . $tld;
        $price = $this->resellerPricing()[$classkey]['addtransferdomain']['1'] ?? null;

        if (!is_numeric($price)) {
            \Log::warning('ResellerClub transfer reseller cost is unavailable', [
                'classkey' => $classkey,
            ]);

            return null;
        }

        return (float) $this->convertLivePriceToBdt($price);
    }

    /**
     * Checks transfer eligibility without initiating or charging for a transfer.
     * Returns null when the eligibility check could not be verified.
     */
    public function validateDomainTransferIn($domain)
    {
        if (!$this->isConfigured()) {
            \Log::warning('ResellerClub transfer validation skipped because the client is not configured', [
                'domain' => $domain,
            ]);

            return null;
        }

        $baseParts = parse_url(trim((string) ($this->config['base_url'] ?? '')));
        if (!$baseParts || strtolower($baseParts['scheme'] ?? '') !== 'https' || empty($baseParts['host'])) {
            \Log::warning('ResellerClub transfer validation skipped because its HTTPS configuration is invalid', [
                'domain' => $domain,
            ]);

            return null;
        }

        $response = $this->request(
            '/domains/validate-transfer.json',
            ['domain-name' => strtolower(trim($domain))],
            'GET',
            true,
            true,
            true
        );
        if (is_bool($response)) {
            return $response;
        }
        if (is_string($response)) {
            $normalized = strtolower(trim($response, " \t\n\r\0\x0B\"'"));
            if ($normalized === 'true') {
                return true;
            }
            if ($normalized === 'false') {
                return false;
            }
        }

        if ($response !== null) {
            \Log::warning('ResellerClub transfer eligibility check could not be completed', [
                'domain' => $domain,
            ]);
        }

        return null;
    }

    public function transferDomain($domain, $authCode)
    {
        if (!is_string($authCode) || trim($authCode) === '') {
            \Log::warning('ResellerClub domain transfer skipped because the authorization code is missing', [
                'domain' => $domain,
            ]);

            return null;
        }

        $parameters = $this->transferRequestParameters($domain);
        if ($parameters === null) {
            return null;
        }

        return $this->v2Request(
            'POST',
            '/domains/' . rawurlencode(strtolower(trim($domain))) . '/transfer-in',
            array_merge(['authCode' => $authCode], $parameters)
        );
    }

    public function isTransferConfiguredForDomain($domain)
    {
        return $this->transferRequestParameters($domain) !== null;
    }

    private function transferRequestParameters($domain)
    {
        if (!$this->isConfigured()) {
            \Log::warning('ResellerClub domain transfer skipped because client or customer is not configured', [
                'domain' => $domain,
            ]);

            return null;
        }

        $customerId = $this->positiveInteger($this->config['customer_id'] ?? null);
        if ($customerId === null) {
            \Log::warning('ResellerClub domain transfer skipped because the customer ID is invalid', [
                'domain' => $domain,
            ]);

            return null;
        }

        $contacts = ResellerClubDomainContacts::forDomain($domain, [
            'reg' => $this->config['reg_contact_id'] ?? null,
            'admin' => $this->config['admin_contact_id'] ?? null,
            'tech' => $this->config['tech_contact_id'] ?? null,
            'billing' => $this->config['billing_contact_id'] ?? null,
        ]);
        if ($contacts === null) {
            \Log::warning('ResellerClub domain transfer skipped because contact IDs are incomplete', [
                'domain' => $domain,
            ]);

            return null;
        }

        $contactIds = [];
        foreach ([
            'reg-contact-id' => 'regContactId',
            'admin-contact-id' => 'adminContactId',
            'tech-contact-id' => 'techContactId',
            'billing-contact-id' => 'billingContactId',
        ] as $legacyKey => $v2Key) {
            $contactId = $this->contactInteger($contacts[$legacyKey]);
            if ($contactId === null) {
                \Log::warning('ResellerClub domain transfer skipped because a contact ID is invalid', [
                    'domain' => $domain,
                    'contact' => $v2Key,
                ]);

                return null;
            }
            $contactIds[$v2Key] = $contactId;
        }

        return array_merge([
            'customerId' => $customerId,
            'invoiceOption' => 'PayInvoice',
            'autoRenew' => false,
        ], $contactIds);
    }

    /**
     * Current available reseller wallet balance and its currency.
     */
    public function walletBalance()
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $resellerId = $this->config['reseller_id'] ?: $this->config['auth_userid'];
        $response = $this->request('/billing/reseller-balance.json', [
            'reseller-id' => $resellerId,
        ]);
        $balanceData = is_array($response) && isset($response['data']) && is_array($response['data'])
            ? $response['data']
            : $response;

        if (
            !is_array($balanceData)
            || isset($balanceData['error'])
            || isset($balanceData['errorcode'])
            || strtoupper((string) ($balanceData['status'] ?? '')) === 'ERROR'
        ) {
            return null;
        }

        $balance = $balanceData['availablebalance'] ?? $balanceData['sellingcurrencybalance'] ?? null;
        $currency = $balanceData['sellingcurrency'] ?? $this->config['price_currency'] ?? null;
        if (!is_numeric($balance) || !$currency) {
            \Log::warning('ResellerClub wallet balance response was invalid', ['response' => $response]);

            return null;
        }

        return [
            'balance' => (float) $balance,
            'currency' => strtoupper((string) $currency),
        ];
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
        if (!$this->isConfigured()) {
            \Log::warning('ResellerClub registration API call skipped because client is not configured', [
                'domain' => $domain,
            ]);

            return null;
        }

        if (!$this->config['customer_id']) {
            \Log::warning('ResellerClub registration API call skipped because customer ID is missing', [
                'domain' => $domain,
            ]);

            return null;
        }

        $nameservers = array_values(array_filter(array_map('trim', $this->config['nameservers'] ?? [])));
        if (!$nameservers) {
            \Log::warning('ResellerClub registration API call skipped because nameservers are missing', [
                'domain' => $domain,
            ]);

            return null;
        }

        $contacts = ResellerClubDomainContacts::forDomain($domain, [
            'reg' => $this->config['reg_contact_id'] ?? null,
            'admin' => $this->config['admin_contact_id'] ?? null,
            'tech' => $this->config['tech_contact_id'] ?? null,
            'billing' => $this->config['billing_contact_id'] ?? null,
        ]);
        if ($contacts === null) {
            \Log::warning('ResellerClub registration API call skipped because contact IDs are incomplete', [
                'domain' => $domain,
            ]);

            return null;
        }

        $parameters = [
            'domain-name' => $domain,
            'years' => $years,
            'customer-id' => $this->config['customer_id'],
            'ns' => $nameservers,
        ];

        return $this->request('/domains/register.json', array_merge($parameters, $contacts, [
            'invoice-option' => 'PayInvoice',
            'protect-privacy' => 'false',
        ]), 'POST');
    }

    public function domainOrderId($domain)
    {
        if (!$this->isConfigured()) {
            \Log::warning('ResellerClub domain order lookup skipped because client is not configured', [
                'domain' => $domain,
            ]);

            return null;
        }

        $response = $this->request('/domains/orderid.json', [
            'domain-name' => strtolower(trim($domain)),
        ], 'GET', true);

        if (is_array($response)) {
            $response = $response[0] ?? $response['orderid'] ?? $response['order-id'] ?? null;
        }

        if (!is_scalar($response) || !ctype_digit((string) $response) || (int) $response < 1) {
            \Log::warning('ResellerClub returned an invalid domain order ID', [
                'domain' => $domain,
                'response' => $response,
            ]);

            return null;
        }

        return (string) $response;
    }

    public function modifyNameservers($orderId, array $nameservers)
    {
        if (!$this->isConfigured()) {
            \Log::warning('ResellerClub nameserver update skipped because client is not configured', [
                'order_id' => $orderId,
            ]);

            return null;
        }

        $nameservers = array_values(array_filter(array_map('trim', $nameservers)));
        if (!is_scalar($orderId) || !ctype_digit((string) $orderId) || (int) $orderId < 1 || count($nameservers) < 2) {
            \Log::warning('ResellerClub nameserver update skipped because its input is incomplete', [
                'order_id' => $orderId,
                'nameserver_count' => count($nameservers),
            ]);

            return null;
        }

        return $this->request('/domains/modify-ns.json', [
            'order-id' => (string) $orderId,
            'ns' => $nameservers,
        ], 'POST');
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

    protected function v2Request($method, $path, array $payload = [])
    {
        $baseUrl = trim((string) ($this->config['v2_base_url'] ?? ''));
        $baseParts = parse_url($baseUrl);
        if (
            !$this->isConfigured()
            || !$baseParts
            || strtolower($baseParts['scheme'] ?? '') !== 'https'
            || empty($baseParts['host'])
            || isset($baseParts['user'])
            || isset($baseParts['pass'])
            || isset($baseParts['query'])
            || isset($baseParts['fragment'])
        ) {
            \Log::warning('ResellerClub v2 request skipped because its HTTPS configuration is invalid', [
                'path' => $path,
            ]);

            return null;
        }

        $url = rtrim($baseUrl, '/') . $path;
        $headers = [
            'Accept: application/json',
            'Authorization: ApiKey ' . $this->config['api_key'],
            'X-User-Id: ' . $this->config['auth_userid'],
        ];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        ];
        $requestBodyBytes = 0;

        if (strtoupper($method) === 'POST') {
            $body = json_encode($payload);
            if ($body === false) {
                \Log::warning('ResellerClub v2 request payload could not be encoded', [
                    'path' => $path,
                ]);

                return null;
            }
            $requestBodyBytes = strlen($body);
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $body;
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        } elseif (strtoupper($method) !== 'GET') {
            throw new \InvalidArgumentException('Unsupported ResellerClub v2 HTTP method.');
        }

        $responseHeaders = [];
        $loggedResponseHeaders = [
            'content-type',
            'server',
            'via',
            'cf-ray',
            'x-cache',
            'x-request-id',
            'request-id',
            'www-authenticate',
        ];
        $options[CURLOPT_HEADERFUNCTION] = function ($curl, $headerLine) use (&$responseHeaders, $loggedResponseHeaders) {
            $length = strlen($headerLine);
            $separator = strpos($headerLine, ':');
            if ($separator !== false) {
                $name = strtolower(trim(substr($headerLine, 0, $separator)));
                if (in_array($name, $loggedResponseHeaders, true)) {
                    $value = trim(substr($headerLine, $separator + 1));
                    $responseHeaders[$name] = substr(preg_replace('/[\r\n\t]+/', ' ', $value), 0, 250);
                }
            }

            return $length;
        };

        $curl = curl_init($url);
        if ($curl === false) {
            \Log::warning('Could not initialize ResellerClub v2 request', ['path' => $path]);

            return null;
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        if ($body === false || $error || $httpStatus < 200 || $httpStatus >= 300) {
            $apiError = null;
            $decodedBody = is_string($body) ? json_decode($body, true) : null;
            if (is_array($decodedBody)) {
                foreach (['message', 'error_description', 'error', 'errorCode', 'code'] as $key) {
                    $value = $decodedBody[$key] ?? null;
                    if (is_array($value)) {
                        $value = $value['message'] ?? null;
                    }
                    if (is_scalar($value) && trim((string) $value) !== '') {
                        $apiError = $this->sanitizeLogText((string) $value, 250);
                        break;
                    }
                }
            }

            \Log::warning('ResellerClub v2 request failed', [
                'method' => strtoupper($method),
                'path' => $path,
                'api_host' => $baseParts['host'],
                'request_payload_keys' => strtoupper($method) === 'POST' ? array_keys($payload) : [],
                'request_body_bytes' => $requestBodyBytes,
                'http_status' => $httpStatus,
                'error' => $error ?: null,
                'api_error' => $apiError,
                'response_content_type' => $contentType ?: null,
                'response_bytes' => is_string($body) ? strlen($body) : 0,
                'response_body_sha256' => is_string($body) ? hash('sha256', $body) : null,
                'response_headers' => $responseHeaders,
                'response_excerpt' => is_string($body) ? $this->safeResponseExcerpt($body) : null,
            ]);

            return null;
        }

        $response = json_decode($body, true);
        if (!is_array($response)) {
            \Log::warning('ResellerClub v2 response was not a JSON object', [
                'method' => strtoupper($method),
                'path' => $path,
                'http_status' => $httpStatus,
            ]);

            return null;
        }

        return $response;
    }

    private function safeResponseExcerpt($body)
    {
        if (!preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $body, $matches)) {
            return null;
        }
        $body = $matches[1];

        $body = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $body);
        $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace('/(api[-_ ]?key|auth(?:orization)?[-_ ]?code|epp(?:[-_ ]?code)?|access[-_ ]?token|token|password|cookie)\s*[:=]\s*["\']?[^"\',;\s}]+/i', '$1=[redacted]', $body);
        $body = preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $body);
        $body = preg_replace('/[\r\n\t ]+/', ' ', $body);

        return substr(trim($body), 0, 300);
    }

    private function sanitizeLogText($text, $limit)
    {
        $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/(api[-_ ]?key|auth(?:orization)?[-_ ]?code|epp(?:[-_ ]?code)?|access[-_ ]?token|token|password|cookie)\s*[:=]\s*["\']?[^"\',;\s}]+/i', '$1=[redacted]', $text);
        $text = preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $text);
        $text = preg_replace('/[\r\n\t ]+/', ' ', $text);

        return substr(trim($text), 0, $limit);
    }

    private function positiveInteger($value)
    {
        if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/', trim((string) $value))) {
            return null;
        }

        $integer = filter_var(trim((string) $value), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);

        return $integer === false ? null : $integer;
    }

    private function contactInteger($value)
    {
        if (!is_scalar($value) || !preg_match('/^(?:-1|[1-9][0-9]*)$/', trim((string) $value))) {
            return null;
        }

        $integer = filter_var(trim((string) $value), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => -1, 'max_range' => 2147483647],
        ]);

        return $integer === false ? null : $integer;
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

    protected function request(
        $path,
        array $parameters = [],
        $method = 'GET',
        $allowScalar = false,
        $ipv4Only = false,
        $requireSuccessStatus = false
    )
    {
        $parameters['auth-userid'] = $this->config['auth_userid'];
        $parameters['api-key'] = $this->config['api_key'];
        $url = rtrim($this->config['base_url'], '/') . $path;
        if ($method === 'GET') {
            $url .= '?' . $this->encodeParameters($parameters);
        }

        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ];
        if ($ipv4Only) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            $options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
        }
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $this->encodeParameters($parameters);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($curl, $options);
        \Log::info('Sending ResellerClub API request', [
            'method' => $method,
            'path' => $path,
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $error || ($requireSuccessStatus && ($httpStatus < 200 || $httpStatus >= 300))) {
            \Log::warning('ResellerClub request failed', [
                'method' => $method,
                'path' => $path,
                'http_status' => $httpStatus,
                'error' => $error,
            ]);

            return null;
        }

        $decoded = json_decode($body, true);
        \Log::info('ResellerClub API response received', [
            'method' => $method,
            'path' => $path,
            'http_status' => $httpStatus,
            'api_status' => is_array($decoded) ? ($decoded['status'] ?? null) : null,
        ]);

        $sensitiveRequest = $path === '/domains/transfer.json';
        if (
            (!is_array($decoded) && !($allowScalar && is_scalar($decoded)))
            || (is_array($decoded) && isset($decoded['status']) && strtoupper($decoded['status']) === 'ERROR')
        ) {
            \Log::warning('ResellerClub API error', $sensitiveRequest
                ? ['path' => $path]
                : ['path' => $path, 'response' => substr($body, 0, 500)]);
        }

        if (is_array($decoded) || ($allowScalar && is_scalar($decoded))) {
            return $decoded;
        }

        return $allowScalar ? trim($body) : null;
    }

    private function encodeParameters(array $parameters)
    {
        $parts = [];

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_scalar($item)) {
                        $parts[] = http_build_query([$key => $item]);
                    }
                }

                continue;
            }

            $part = http_build_query([$key => $value]);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        return implode('&', $parts);
    }
}
