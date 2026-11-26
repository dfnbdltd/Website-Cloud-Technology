<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * "Is this domain OK to connect a hosting plan to?"
 *
 * Hosting is only sold for a domain that is ALREADY registered (somewhere), so
 * the answer is the opposite of the domain search:
 *
 *   ResellerClub says "regthroughus" / "regthroughothers"  -> registered  -> OK
 *   ResellerClub says "available"                           -> not registered yet -> not OK,
 *                                                              register it first
 *   anything else / API down / not configured               -> could not verify -> not OK
 *
 * It asks ResellerClub with the very same call the domain search uses
 * (ResellerClubClient::checkAvailability($name, $tld)), so there is one source
 * of truth for "is this name taken".
 *
 * Safe by default: whenever the answer is not a clear "registered", the
 * customer cannot continue. The server runs this again when the domain is
 * submitted (HostingCheckoutController::storeDomain) — the tick in the browser
 * is only feedback, never the proof.
 */
class HostingDomainCheckService
{
    const STATE_OK = 'ok';
    const STATE_AVAILABLE = 'available';     // not registered yet
    const STATE_INVALID = 'invalid';
    const STATE_UNVERIFIED = 'unverified';   // lookup failed / unknown answer

    /** A clear answer is reused this long, so Check + Continue cost one API call, not two. */
    const CACHE_SECONDS = 300;

    /** ResellerClub availability statuses. */
    const REGISTERED_STATUSES = ['regthroughus', 'regthroughothers'];
    const AVAILABLE_STATUSES = ['available', 'notinuse'];

    /**
     * Two-part public suffixes, so "shop.example.com.bd" is read as
     * example + com.bd (not "com" + "bd"). Anything not listed is a one-part TLD.
     */
    const MULTI_PART_SUFFIXES = [
        'com.bd', 'net.bd', 'org.bd', 'edu.bd', 'gov.bd', 'ac.bd', 'info.bd', 'co.bd', 'mil.bd',
        'co.uk', 'org.uk', 'me.uk', 'ltd.uk', 'plc.uk',
        'com.au', 'net.au', 'org.au', 'co.nz', 'co.in', 'net.in', 'org.in',
        'com.pk', 'com.np', 'com.lk', 'com.sg', 'com.my', 'com.br', 'com.cn',
        'co.za', 'co.jp', 'co.kr', 'com.tr', 'com.mx', 'com.ar', 'com.eg',
    ];

    /** How the "Check" button works (the dropdown beside the search box). */
    const MODE_OWN = 'own';           // I already have a registered domain (default)
    const MODE_REGISTER = 'register'; // Register a new domain
    const MODE_TRANSFER = 'transfer'; // Transfer a domain in
    const MODES = [self::MODE_OWN, self::MODE_REGISTER, self::MODE_TRANSFER];

    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    /**
     * @param  mixed  $input  what the customer typed
     * @return array  [
     *   'ok'          => bool    may the customer continue,
     *   'state'       => string  ok | available | invalid | unverified,
     *   'domain'      => string|null  the cleaned name (what the order will carry),
     *   'registrable' => string|null  the name that was looked up (example.com),
     *   'message'     => string  shown under the field,
     *   'register_url'=> string|null  only when the domain is not registered yet,
     * ]
     */
    public function check($input, $fresh = false)
    {
        $domain = HostingOrderService::normalizeDomain($input);
        $parts = $domain === null ? null : self::registrableParts($domain);

        if ($domain === null || $parts === null) {
            return $this->result(self::STATE_INVALID, $domain, null, 'Enter a valid domain name, like example.com.');
        }

        $registrable = $parts['name'] . '.' . $parts['tld'];

        // Rollback lever: skip the ResellerClub lookup entirely.
        if (!config('services.resellerclub.hosting_domain_check', true)) {
            return $this->result(self::STATE_OK, $domain, $registrable, $registrable . ' is ready to connect.');
        }

        $cacheKey = 'hosting.domaincheck.v1.' . $registrable;
        // $fresh skips the remembered answer (used when the buyer comes back from registering the domain).
        $state = $fresh ? null : Cache::get($cacheKey);

        if (!in_array($state, [self::STATE_OK, self::STATE_AVAILABLE], true)) {
            $state = $this->lookup($parts['name'], $parts['tld']);

            // Only a clear answer is remembered; a failure must be retried at once.
            if (in_array($state, [self::STATE_OK, self::STATE_AVAILABLE], true)) {
                Cache::put($cacheKey, $state, self::CACHE_SECONDS);
            }
        }

        if ($state === self::STATE_OK) {
            return $this->result(self::STATE_OK, $domain, $registrable, $registrable . ' is registered. You can continue.');
        }

        if ($state === self::STATE_AVAILABLE) {
            return $this->result(
                self::STATE_AVAILABLE,
                $domain,
                $registrable,
                $registrable . ' is not registered yet, so hosting cannot be connected to it. Register the domain first, then come back.',
                route('domains.index', ['domain' => $registrable])
            );
        }

        return $this->result(
            self::STATE_UNVERIFIED,
            $domain,
            $registrable,
            'We could not verify this domain right now. Please try again in a moment.'
        );
    }

    /** Whitelist: anything that is not a known mode is the default (own domain). */
    public static function normalizeMode($mode)
    {
        $mode = is_scalar($mode) ? strtolower(trim((string) $mode)) : '';

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_OWN;
    }

    /**
     * The "Check" button for the chosen mode. Same result shape as check(), plus
     *   'mode'  the mode that was used,
     *   'price' BDT price (registration for 1 year / transfer) or null.
     *
     *   own      -> check(): the domain must already be registered (ResellerClub).
     *   register -> the name must be FREE (ResellerClubClient::checkAvailability) and sold here.
     *   transfer -> the name must be eligible to transfer in (ResellerClubClient::validateDomainTransferIn)
     *               and a live transfer price must exist.
     *
     * Registering / transferring is then done by the normal domain flow
     * (HostingCheckoutController::storeDomain hands the customer over to it).
     */
    public function checkForMode($mode, $input)
    {
        $mode = self::normalizeMode($mode);

        if ($mode === self::MODE_OWN) {
            return $this->withMode($this->check($input), $mode, null);
        }

        $domain = HostingOrderService::normalizeDomain($input);
        $parts = $domain === null ? null : self::registrableParts($domain);
        if ($domain === null || $parts === null) {
            return $this->withMode($this->result(self::STATE_INVALID, $domain, null, 'Enter a valid domain name, like example.com.'), $mode, null);
        }

        $registrable = $parts['name'] . '.' . $parts['tld'];

        return $mode === self::MODE_REGISTER
            ? $this->checkRegister($parts, $registrable)
            : $this->checkTransfer($parts, $registrable);
    }

    private function checkRegister(array $parts, $registrable)
    {
        $mode = self::MODE_REGISTER;
        $plan = null;
        try {
            $plan = $this->resellerClub->catalog()[$parts['tld']] ?? null;
        } catch (\Throwable $e) {
            \Log::warning('Hosting register check could not read the catalog', ['error' => $e->getMessage()]);
        }
        if (!$plan) {
            return $this->withMode($this->result(self::STATE_INVALID, $registrable, $registrable, '.' . $parts['tld'] . ' domains cannot be registered here. Try .com, .net or another extension we sell.'), $mode, null);
        }

        $state = $this->lookup($parts['name'], $parts['tld']);
        $price = isset($plan['price']) ? (float) $plan['price'] : null;

        if ($state === self::STATE_AVAILABLE) {
            return $this->withMode($this->result(
                self::STATE_AVAILABLE,
                $registrable,
                $registrable,
                $registrable . ' is available' . ($price ? ' for ৳' . HostingOrderService::money($price) . ' / year' : '') . '. Continue to register it, then you will come back to pay for hosting.'
            ), $mode, $price, true);
        }

        if ($state === self::STATE_OK) {
            return $this->withMode($this->result(
                self::STATE_OK,
                $registrable,
                $registrable,
                $registrable . ' is already registered. If it is yours, choose "I already have a registered domain".'
            ), $mode, null, false);
        }

        return $this->withMode($this->result(self::STATE_UNVERIFIED, $registrable, $registrable, 'We could not check this domain right now. Please try again in a moment.'), $mode, null);
    }

    private function checkTransfer(array $parts, $registrable)
    {
        $mode = self::MODE_TRANSFER;

        try {
            $eligible = $this->resellerClub->validateDomainTransferIn($registrable);
        } catch (\Throwable $e) {
            \Log::warning('Hosting transfer check failed', ['domain' => $registrable, 'error' => $e->getMessage()]);
            $eligible = null;
        }

        if ($eligible === null) {
            return $this->withMode($this->result(self::STATE_UNVERIFIED, $registrable, $registrable, 'We could not verify this domain’s transfer eligibility. No payment has been taken; please try again shortly.'), $mode, null);
        }
        if (!$eligible) {
            return $this->withMode($this->result(self::STATE_INVALID, $registrable, $registrable, 'This domain is not eligible for transfer. Disable its transfer lock at the current registrar and check the 60-day transfer rule.'), $mode, null);
        }

        $price = null;
        try {
            $price = $this->resellerClub->liveTransferPrice($parts['tld']);
        } catch (\Throwable $e) {
            \Log::warning('Hosting transfer price lookup failed', ['domain' => $registrable, 'error' => $e->getMessage()]);
        }
        if ($price === null) {
            return $this->withMode($this->result(self::STATE_UNVERIFIED, $registrable, $registrable, 'A live transfer price is not available for this domain extension. Please contact support.'), $mode, null);
        }

        return $this->withMode($this->result(
            self::STATE_OK,
            $registrable,
            $registrable,
            $registrable . ' can be transferred for ৳' . HostingOrderService::money($price) . '. Enter its authorization (EPP) code, then continue.'
        ), $mode, (float) $price, true);
    }

    /** Add the mode + price to a result(); $ok overrides 'ok' for the register/transfer modes. */
    private function withMode(array $result, $mode, $price, $ok = null)
    {
        $result['mode'] = $mode;
        $result['price'] = $price;
        if ($ok !== null) {
            $result['ok'] = $ok;
        } elseif ($mode !== self::MODE_OWN) {
            $result['ok'] = false;
        }

        return $result;
    }

    /**
     * Ask ResellerClub (same call as the domain search). Returns STATE_OK for a
     * registered name, STATE_AVAILABLE for a free one, STATE_UNVERIFIED otherwise.
     */
    private function lookup($name, $tld)
    {
        try {
            $remote = $this->resellerClub->checkAvailability($name, $tld);
        } catch (\Throwable $e) {
            \Log::warning('Hosting domain check failed', ['domain' => $name . '.' . $tld, 'error' => $e->getMessage()]);

            return self::STATE_UNVERIFIED;
        }

        if (!is_array($remote)) {
            return self::STATE_UNVERIFIED;
        }

        $entry = array_change_key_case($remote, CASE_LOWER)[$name . '.' . $tld] ?? null;
        $status = is_array($entry) ? strtolower(trim((string) ($entry['status'] ?? ''))) : '';

        if (in_array($status, self::REGISTERED_STATUSES, true)) {
            return self::STATE_OK;
        }
        if (in_array($status, self::AVAILABLE_STATUSES, true)) {
            return self::STATE_AVAILABLE;
        }

        \Log::warning('Hosting domain check got an answer it cannot use', [
            'domain' => $name . '.' . $tld,
            'status' => $status !== '' ? $status : null,
        ]);

        return self::STATE_UNVERIFIED;
    }

    /**
     * The registrable part of a hostname: "www.shop.example.com" => example + com,
     * "blog.example.com.bd" => example + com.bd. Null when there is no name part.
     *
     * @param  string  $host  already normalised (lower-case, no scheme/path)
     * @return array|null  ['name' => 'example', 'tld' => 'com']
     */
    public static function registrableParts($host)
    {
        $labels = array_values(array_filter(explode('.', strtolower(trim((string) $host))), 'strlen'));
        $count = count($labels);

        if ($count < 2) {
            return null;
        }

        $twoPart = $labels[$count - 2] . '.' . $labels[$count - 1];
        if (in_array($twoPart, self::MULTI_PART_SUFFIXES, true)) {
            return $count >= 3
                ? ['name' => $labels[$count - 3], 'tld' => $twoPart]
                : null;
        }

        return ['name' => $labels[$count - 2], 'tld' => $labels[$count - 1]];
    }

    private function result($state, $domain, $registrable, $message, $registerUrl = null)
    {
        return [
            'ok' => $state === self::STATE_OK,
            'state' => $state,
            'domain' => $domain,
            'registrable' => $registrable,
            'message' => $message,
            'register_url' => $registerUrl,
        ];
    }
}
