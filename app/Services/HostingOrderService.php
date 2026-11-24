<?php

namespace App\Services;

use App\Models\HostingOrder;
use App\Models\HostingPlan;
use App\User;
use Illuminate\Support\Facades\DB;

/**
 * Prices a hosting selection for the connect-domain and checkout pages.
 *
 * The cart (session 'hosting_cart') only stores region + type + plan id +
 * billing period + domain. Every price shown is re-derived here from
 * HostingPlanService on each page view, so a stale or tampered cart can never
 * change what the customer sees.
 *
 * place() saves the order + its unpaid invoice (money frozen at that moment);
 * the bKash payment is handled by HostingBkashController.
 */
class HostingOrderService
{
    /** Billing period key => label, price unit, months. */
    const PERIODS = [
        'monthly' => ['label' => 'Monthly', 'unit' => 'month', 'months' => 1],
        'yearly' => ['label' => 'Yearly', 'unit' => 'year', 'months' => 12],
        'twoYear' => ['label' => '2 Years', 'unit' => '2 years', 'months' => 24],
        'threeYear' => ['label' => '3 Years', 'unit' => '3 years', 'months' => 36],
    ];

    /** The WordPress page links use these slugs; accept them too. */
    const PERIOD_ALIASES = [
        'two-year' => 'twoYear',
        'three-year' => 'threeYear',
        'twoyear' => 'twoYear',
        'threeyear' => 'threeYear',
    ];

    private $plans;

    public function __construct(HostingPlanService $plans)
    {
        $this->plans = $plans;
    }

    /** @return string|null  monthly | yearly | twoYear | threeYear */
    public static function normalizePeriod($period)
    {
        if (!is_scalar($period)) {
            return null;
        }

        $period = (string) $period;
        if (isset(self::PERIODS[$period])) {
            return $period;
        }

        $period = strtolower($period);

        return self::PERIOD_ALIASES[$period] ?? null;
    }

    /**
     * The plan card + billing period with today's BDT price, or null when the
     * section/plan is gone or hidden, the period is not sold on that plan, or
     * the price is not a positive number.
     *
     * @param  string  $region  local | usa | wordpress
     * @param  string  $type    shared | vps | dedicated | wordpress
     * @param  string  $planId  HostingPlanService card 'id'
     * @param  string  $period  monthly | yearly | twoYear | threeYear (slugs accepted)
     * @return array|null
     */
    public function quote($region, $type, $planId, $period)
    {
        $section = HostingPlan::normalizeSection((string) $region, (string) $type);
        $period = self::normalizePeriod($period);
        $planId = is_scalar($planId) ? (string) $planId : '';

        if ($section === null || $period === null || $planId === '') {
            return null;
        }
        [$region, $type] = $section;

        foreach ($this->plans->plans($type, $region) as $card) {
            if ((string) ($card['id'] ?? '') !== $planId) {
                continue;
            }

            $offered = !empty($card['terms'][$period]);
            $visible = !isset($card['visibility']) || !empty($card['visibility'][$period]);
            $price = isset($card['prices'][$period]) ? (float) $card['prices'][$period] : 0.0;

            if (!$offered || !$visible || $price <= 0) {
                return null;
            }

            $meta = self::PERIODS[$period];

            return [
                'region' => $region,
                'type' => $type,
                'region_label' => HostingPlan::REGION_SHORT[$region],
                'type_label' => HostingPlan::TYPE_LABELS[$type],
                'product' => self::productLabel($region, $type),
                'plan' => $card,
                'plan_id' => (string) $card['id'],
                'name' => $card['names'][$period] ?? $card['name'],
                'period' => $period,
                'period_label' => $meta['label'],
                'unit' => $meta['unit'],
                'months' => $meta['months'],
                'price' => $price,
                'total' => $price,
                'specs' => self::specsLine($card, $period),
                'plans_url' => self::plansUrl($region, $type),
            ];
        }

        return null;
    }

    /** Payment methods a hosting order can be placed with (Nagad comes later). */
    const PAYMENT_METHODS = ['bkash'];

    /**
     * Save the order and its (unpaid) invoice. Money is frozen here, from the
     * freshly re-derived quote - never from the browser.
     *
     * @param  array      $quote     result of quote()
     * @param  string     $domain    already checked/normalised
     * @param  \App\User $owner     whose hosting it is (the customer when buying for one)
     * @param  \App\User $placedBy  who is checking out and paying
     * @param  string     $method    one of PAYMENT_METHODS
     * @return \App\Models\HostingOrder
     */
    public function place(array $quote, $domain, User $owner, User $placedBy, $method)
    {
        return DB::transaction(function () use ($quote, $domain, $owner, $placedBy, $method) {
            $order = HostingOrder::create([
                'reference' => 'HST-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
                'user_id' => $owner->id,
                'placed_by_user_id' => $placedBy->id,
                'region' => $quote['region'],
                'type' => $quote['type'],
                'plan_id' => $quote['plan_id'],
                'plan_name' => $quote['name'],
                'product' => $quote['product'],
                'period' => $quote['period'],
                'period_label' => $quote['period_label'],
                'months' => (int) $quote['months'],
                'domain' => $domain,
                'total' => $quote['total'],
                'status' => HostingOrder::STATUS_PAYMENT_PENDING,
                'payment_method' => $method,
                'payment_status' => 'pending',
                'provider' => $method,
                // The owner's account email is used everywhere (invoice, mail, pages).
                'customer_name' => $owner->name,
                'customer_email' => $owner->email,
                'customer_phone' => (string) $owner->phone,
            ]);

            app(InvoiceService::class)->createForHostingOrder($order);

            return $order;
        });
    }

    /** "Local Shared Hosting", "USA VPS Hosting", "WordPress Hosting". */
    public static function productLabel($region, $type)
    {
        if ($region === HostingPlan::REGION_WORDPRESS) {
            return 'WordPress Hosting';
        }

        return HostingPlan::REGION_SHORT[$region] . ' ' . HostingPlan::TYPE_LABELS[$type] . ' Hosting';
    }

    /** The public page that lists these plans (used for Back / breadcrumbs). */
    public static function plansUrl($region, $type)
    {
        return $region === HostingPlan::REGION_WORDPRESS
            ? route('hosting.wordpress')
            : route('hosting.region', ['region' => $region, 'type' => $type]);
    }

    /** First few headline specs of the chosen term: "3 websites, 20 GB NVMe storage, ...". */
    private static function specsLine(array $card, $period)
    {
        $set = $card['feature_sets'][$period]['primary'] ?? ($card['primary_features'] ?? []);
        $parts = [];

        foreach ($set as $feature) {
            if (isset($feature['available']) && !$feature['available']) {
                continue;
            }

            $text = isset($feature['lead'])
                ? trim($feature['lead'] . ' ' . ($feature['name'] ?? ''))
                : trim((string) ($feature['monthly'] ?? '') . ' ' . (string) ($feature['name'] ?? ''));

            if ($text !== '') {
                $parts[] = $text;
            }
            if (count($parts) >= 3) {
                break;
            }
        }

        return implode(', ', $parts);
    }

    /** Same hostname rule as SSL (a "*." name is not valid for hosting). */
    public static function normalizeDomain($input)
    {
        return SslOrderService::normalizeDomain($input, false);
    }

    /** Whole taka when the price has no fraction, else 2 decimals. */
    public static function money($amount)
    {
        $amount = (float) $amount;
        $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;

        return number_format($amount, $decimals);
    }
}
