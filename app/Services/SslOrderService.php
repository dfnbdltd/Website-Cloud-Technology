<?php

namespace App\Services;

use App\Models\SslOrder;
use App\Models\SslPlan;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Prices and places SSL orders.
 *
 * The price a customer is charged is NEVER read from the browser or from the
 * session: it is re-derived here from SslPlanService on every page view and at
 * the moment the order is placed, so a stale or tampered cart cannot change it.
 *
 * This class stops at "order saved, invoice issued, waiting for payment". The
 * payment gateway and the ResellerClub order call hook in afterwards (see
 * SslCheckoutController::placeOrder).
 */
class SslOrderService
{
    /** Same hostname rule the domain-transfer form uses. */
    const DOMAIN_PATTERN = '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i';

    /** Payment methods an SSL order can be placed with (gateways come later). */
    const PAYMENT_METHODS = ['bkash', 'nagad'];

    private $plans;

    public function __construct(SslPlanService $plans)
    {
        $this->plans = $plans;
    }

    /**
     * The plan card and chosen term for a plan key + number of years, with
     * today's BDT price. Null when the plan is gone/hidden, the term is not
     * sold, or the price is not a positive number.
     *
     * @param  string  $planKey
     * @param  int     $years
     * @return array|null  ['plan' => card, 'term' => term, 'years' => int, 'total' => float]
     */
    public function quote($planKey, $years)
    {
        $planKey = (string) $planKey;
        $years = (int) $years;

        if ($planKey === '' || $years < 1) {
            return null;
        }

        foreach ($this->plans->plans() as $card) {
            if (($card['key'] ?? null) !== $planKey) {
                continue;
            }

            $term = isset($card['terms'][$years]) ? $card['terms'][$years] : null;
            if (!$term || (float) $term['total'] <= 0) {
                return null;
            }

            return [
                'plan' => $card,
                'term' => $term,
                'years' => $years,
                'total' => (float) $term['total'],
            ];
        }

        return null;
    }

    /** Wildcard certificates cover "*.example.com". */
    public static function isWildcard(array $plan)
    {
        $haystack = strtolower(($plan['key'] ?? '') . ' ' . ($plan['name'] ?? ''));

        return strpos($haystack, 'wildcard') !== false;
    }

    /**
     * Clean a typed domain into the name the certificate is issued for, or
     * null when it is not a valid hostname.
     *
     * Accepts what people actually paste ("https://Example.com/", "example.com.")
     * and keeps the name exactly as the customer needs it on the certificate
     * (a leading "www." is NOT stripped: www.example.com and example.com are
     * different certificate names). Wildcard plans always return "*.name";
     * other plans refuse a "*." prefix.
     *
     * @param  mixed  $input
     * @param  bool   $wildcard
     * @return string|null
     */
    public static function normalizeDomain($input, $wildcard = false)
    {
        if (!is_scalar($input)) {
            return null;
        }

        $domain = strtolower(trim((string) $input));
        $domain = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $domain);
        $domain = preg_replace('#[/?\#].*$#', '', $domain);
        $domain = rtrim($domain, '.');

        $hadWildcard = strpos($domain, '*.') === 0;
        if ($hadWildcard) {
            $domain = substr($domain, 2);
        }

        if (!preg_match(self::DOMAIN_PATTERN, $domain)) {
            return null;
        }

        if ($wildcard) {
            return '*.' . $domain;
        }

        return $hadWildcard ? null : $domain;
    }

    /**
     * Find an existing, email-verified customer by email or customer ID
     * (CT-000123). Used for "buying for a customer".
     *
     * @param  mixed  $identifier
     * @return \App\User|null
     */
    public function findCustomer($identifier)
    {
        return \App\Support\CustomerLookup::find($identifier);
    }

    /**
     * Save the order and its (unpaid) invoice. Money is frozen here.
     *
     * @param  array        $quote     result of quote()
     * @param  string       $domain    already normalised
     * @param  \App\User    $owner     who the certificate belongs to
     * @param  \App\User    $placedBy  who is checking out
     * @param  string       $method    one of PAYMENT_METHODS
     * @return \App\Models\SslOrder
     */
    public function place(array $quote, $domain, User $owner, User $placedBy, $method)
    {
        $plan = $quote['plan'];
        $years = (int) $quote['years'];

        $planModel = Schema::hasTable('ssl_plans')
            ? SslPlan::where('plan_key', $plan['key'])->first()
            : null;
        $resellerPlanId = $planModel ? $planModel->resellerclubPlanId() : null;

        $rate = (float) app(ExchangeRateService::class)->usdToBdt()['rate'];
        $cost = $this->costSnapshot($resellerPlanId, $years * 12, $rate);

        return DB::transaction(function () use ($quote, $plan, $years, $domain, $owner, $placedBy, $method, $planModel, $resellerPlanId, $rate, $cost) {
            $order = SslOrder::create([
                'reference' => 'SSL-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
                'user_id' => $owner->id,
                'placed_by_user_id' => $placedBy->id,
                'ssl_plan_id' => $planModel ? $planModel->id : null,
                'plan_key' => $plan['key'],
                'plan_name' => $plan['name'],
                'resellerclub_plan_id' => $resellerPlanId,
                'domain' => $domain,
                'years' => $years,
                'total' => $quote['total'],
                'purchase_price' => $cost,
                'exchange_rate' => $rate,
                'price_source' => $plan['source'] ?? null,
                'status' => SslOrder::STATUS_PAYMENT_PENDING,
                'payment_method' => $method,
                'payment_status' => 'pending',
                'provider' => $method,
                'customer_name' => $owner->name,
                'customer_email' => $owner->email,
                'customer_phone' => (string) $owner->phone,
            ]);

            app(InvoiceService::class)->createForSslOrder($order);

            return $order;
        });
    }

    /**
     * Your ResellerClub cost for this term in BDT, or null if it cannot be
     * read. Never blocks an order: a missing cost only means no profit figure.
     */
    private function costSnapshot($resellerPlanId, $months, $rate)
    {
        if ($resellerPlanId === null || $rate <= 0) {
            return null;
        }

        try {
            $client = app(ResellerClubClient::class);
            if (!$client->isConfigured()) {
                return null;
            }

            $pricing = $client->sslPlanPricing([$resellerPlanId]);
            $perMonthUsd = isset($pricing[$resellerPlanId]['add'][$months]['cost'])
                ? $pricing[$resellerPlanId]['add'][$months]['cost']
                : null;

            return is_numeric($perMonthUsd) ? round((float) $perMonthUsd * $months * $rate, 2) : null;
        } catch (\Throwable $e) {
            Log::warning('SSL order cost snapshot unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
