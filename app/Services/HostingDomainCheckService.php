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
    public function check($input)
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
        $state = Cache::get($cacheKey);

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
