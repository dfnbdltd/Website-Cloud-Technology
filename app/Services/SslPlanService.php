<?php

namespace App\Services;

use App\Models\SslPlan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Reads SSL plans for the public / client cards.
 *
 * Source of truth: the ssl_plans / ssl_plan_features tables. When those are
 * absent or empty (e.g. a fresh checkout before `php artisan migrate`) this
 * falls back to config/ssl_plans.php so the page is never blank.
 *
 * LIVE PRICING
 * ------------
 * A plan with a `resellerclub_plan_id` shows the ResellerClub selling price
 * converted to BDT. Everything else (no link, API down, integration disabled,
 * kill switch off) keeps using the typed BDT columns, so a customer page never
 * depends on a third party being reachable.
 *
 * Only the SELLING price is ever fetched here — see loadLive(). Cost and
 * profit are admin-only and must never enter the arrays this class returns.
 */
class SslPlanService
{
    /** Billing terms offered when a plan is priced by hand, in display order. */
    const TERMS = [1 => '1 Year', 2 => '2 Years', 3 => '3 Years'];

    /**
     * Tenures ResellerClub sells, mapped to the years the card labels use.
     * 36 months is deliberately absent: the API has no 3-year SSL tenure.
     */
    const LIVE_TERMS = [12 => 1, 24 => 2];

    /**
     * The tenure every plan must be sellable at, in months, plus the term key
     * its BDT total is filed under. A plan whose 12-month selling price is
     * unknown cannot be shown as a live card.
     */
    const LIVE_BASE_MONTHS = 12;
    const LIVE_BASE_YEARS = 1;

    private static $tablesMissing;

    public function plans()
    {
        if (!$this->isManaged()) {
            return $this->legacy();
        }

        $plans = SslPlan::query()
            ->where('is_visible', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->with('features')
            ->get();

        $live = $this->loadLive($plans);

        $resolved = $plans->map(function (SslPlan $plan) use ($live) {
            $planId = $plan->resellerclubPlanId();
            $prices = ($planId !== null && isset($live[$planId])) ? $live[$planId] : null;

            return $this->resolve($plan, $prices);
        })->all();

        // Admin can no longer type a price, so a plan that has no live price
        // AND no saved fallback price (a brand-new plan while the API is down)
        // would show a free certificate. Hide it instead of showing ৳0.
        return array_values(array_filter($resolved, function (array $card) {
            return !(($card['source'] ?? '') === 'local' && (float) ($card['monthly'] ?? 0) <= 0);
        }));
    }

    /**
     * ResellerClub selling prices for the linked plans, or [] on any failure.
     *
     * ONE HTTP call per request for the whole page (the client caches it for 5
     * minutes and takes one list, not one call per plan), and never the cost
     * endpoint — the customer side has no use for cost.
     *
     * Every failure mode is swallowed on purpose: an SSL pricing page must not
     * 500 because a registrar is unreachable. Callers fall back to typed prices.
     *
     * @param  \Illuminate\Support\Collection  $plans
     * @return array  planId => price rows
     */
    private function loadLive($plans)
    {
        if (!config('services.resellerclub.ssl_live_pricing', true)) {
            return [];
        }

        $planIds = [];
        foreach ($plans as $plan) {
            $planId = $plan->resellerclubPlanId();
            if ($planId !== null) {
                $planIds[$planId] = $planId;
            }
        }

        if (!$planIds) {
            return [];
        }

        try {
            $client = app(ResellerClubClient::class);

            if (!$client->isConfigured()) {
                return [];
            }

            $prices = $client->sslSellingPricing(array_values($planIds));

            return is_array($prices) ? $prices : [];
        } catch (\Throwable $e) {
            // No secrets or response bodies in the message: just the reason.
            Log::warning('SSL live pricing unavailable; using typed prices', [
                'plan_ids' => array_values($planIds),
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function isManaged()
    {
        if (self::$tablesMissing === null) {
            self::$tablesMissing = !Schema::hasTable('ssl_plans') || !Schema::hasTable('ssl_plan_features');
        }

        return !self::$tablesMissing && SslPlan::query()->exists();
    }

    /**
     * Resolve one plan into the array the Blade partial consumes.
     *
     * `$live` is this plan's ResellerClub price rows (add/renew per tenure in
     * USD/month) or null. Null — or a plan with no 12-month selling price —
     * means "price it from the typed columns", which is exactly the behaviour
     * that shipped before live pricing, so this stays byte-for-byte identical
     * for unlinked plans.
     *
     * The returned array never carries cost or profit: those are admin-only.
     *
     * @param  array|null  $live
     * @return array
     */
    public function resolve(SslPlan $plan, array $live = null)
    {
        if ($live !== null && $this->hasLiveBasePrice($live)) {
            return $this->resolveLive($plan, $live);
        }

        return $this->resolveTyped($plan);
    }

    /**
     * Is this plan sellable at the API price? Needs a positive 12-month
     * selling price; a cost-only row is not a price a customer can be shown.
     *
     * @param  array  $live
     * @return bool
     */
    private function hasLiveBasePrice(array $live)
    {
        $selling = isset($live['add'][self::LIVE_BASE_MONTHS]['selling'])
            ? $live['add'][self::LIVE_BASE_MONTHS]['selling']
            : null;

        return is_numeric($selling) && (float) $selling > 0;
    }

    /**
     * Live ResellerClub pricing converted to BDT.
     *
     * The conversion deliberately multiplies the USD amount FIRST and rounds
     * ONCE, per term, at the end:
     *
     *   total = round(usd_per_month × months × rate)
     *
     * Rounding the per-month USD figure to whole taka before multiplying (what
     * ResellerClubClient::convertLivePriceToBdt does for domains) would be
     * badly wrong here — $0.79 would become ৳95/month and ৳1,140/year instead
     * of the correct ৳1,138.
     *
     * Terms come from what ResellerClub actually sells: only tenures with a
     * selling price are offered, so a plan without a 24-month price shows a
     * single-option term list rather than inventing a number.
     *
     * @param  array  $live
     * @return array
     */
    private function resolveLive(SslPlan $plan, array $live)
    {
        $rate = (float) app(ExchangeRateService::class)->usdToBdt()['rate'];
        if ($rate <= 0) {
            // A broken/zero rate would show ৳0 certificates.
            return $this->resolveTyped($plan);
        }

        $basePerMonthUsd = (float) $live['add'][self::LIVE_BASE_MONTHS]['selling'];
        $terms = [];

        foreach (self::LIVE_TERMS as $months => $years) {
            $perMonthUsd = isset($live['add'][$months]['selling']) ? $live['add'][$months]['selling'] : null;
            if (!is_numeric($perMonthUsd) || (float) $perMonthUsd <= 0) {
                continue;
            }

            $total = round((float) $perMonthUsd * $months * $rate);
            // What the same span would cost at the 12-month rate: the honest
            // "savings" figure for a longer term, from API prices only.
            $full = round($basePerMonthUsd * $months * $rate);
            $savings = max(0, $full - $total);

            $terms[$years] = [
                'years' => $years,
                'label' => self::TERMS[$years],
                'discount' => $full > 0 ? round($savings / $full * 100, 2) : 0,
                'total' => $total,
                'savings' => $savings,
                'per_month' => round($total / $months),
            ];
        }

        if (!$terms) {
            return $this->resolveTyped($plan);
        }

        $renewMonthlyUsd = isset($live['renew'][self::LIVE_BASE_MONTHS]['selling'])
            && is_numeric($live['renew'][self::LIVE_BASE_MONTHS]['selling'])
            && (float) $live['renew'][self::LIVE_BASE_MONTHS]['selling'] > 0
                ? (float) $live['renew'][self::LIVE_BASE_MONTHS]['selling']
                : $basePerMonthUsd;

        return [
            'id' => $plan->id,
            'key' => $plan->plan_key,
            'name' => $plan->name,
            'tagline' => (string) $plan->tagline,
            'badge' => $plan->badge,
            'buy_route' => null,
            // Headline / renewal figures the card prints, in BDT.
            'monthly' => $terms[self::LIVE_BASE_YEARS]['per_month'],
            'renewal' => round($renewMonthlyUsd * $rate),
            'features' => $this->features($plan),
            'terms' => $terms,
            'default' => $terms[self::LIVE_BASE_YEARS],
            // Extra key for debugging/admin; the partial ignores unknown keys.
            'source' => 'resellerclub',
        ];
    }

    /**
     * The typed BDT price path — unchanged maths, used whenever live data is
     * unusable. `total` is rounded to whole taka so a BDT price never reads
     * ৳2,559.60, and `per_month` is the effective monthly for that term.
     *
     * @return array
     */
    private function resolveTyped(SslPlan $plan)
    {
        $terms = [];
        foreach (self::TERMS as $years => $label) {
            $full = (float) $plan->monthly_price * 12 * $years;
            $discount = $plan->discountFor($years);
            $total = round($full * (1 - ($discount / 100)));

            $terms[$years] = [
                'years' => $years,
                'label' => $label,
                'discount' => $discount,
                'total' => $total,
                'savings' => max(0, round($full) - $total),
                'per_month' => round($total / ($years * 12)),
            ];
        }

        return [
            'id' => $plan->id,
            'key' => $plan->plan_key,
            'name' => $plan->name,
            'tagline' => (string) $plan->tagline,
            'badge' => $plan->badge,
            'buy_route' => null,
            'monthly' => (float) $plan->monthly_price,
            'renewal' => (float) $plan->renewal_price,
            'features' => $this->features($plan),
            'terms' => $terms,
            // 1 year is what the card quotes by default.
            'default' => $terms[1],
            'source' => 'local',
        ];
    }

    /**
     * The feature tick list, shaped for the card partial.
     *
     * `shown` is whether the admin wants the row on the card at all; `available`
     * is whether it gets a tick or a cross. They are deliberately separate:
     * hiding a feature must not be confused with marking it unavailable.
     *
     * Hidden rows are dropped HERE rather than in the Blade partial, because a
     * `@php` block inside the card loop makes the Blade compiler emit raw
     * markup instead of a loop (the file mixes inline @php(...) statements with
     * the multi-line tags). This array only ever feeds a customer card, so the
     * service is the right place — the admin editor reads the model directly.
     *
     * @return array
     */
    private function features(SslPlan $plan)
    {
        $features = [];

        foreach ($plan->features as $feature) {
            $shown = method_exists($feature, 'isShownToCustomers')
                ? $feature->isShownToCustomers()
                : (bool) $feature->is_available;

            if (!$shown) {
                continue;
            }

            $features[] = [
                'text' => $feature->feature,
                'available' => (bool) $feature->is_available,
                'shown' => true,
            ];
        }

        return $features;
    }

    /**
     * Config fallback for an unmigrated install.
     */
    private function legacy()
    {
        $config = config('ssl_plans', []);
        $plans = [];

        foreach ((array) ($config['plans'] ?? []) as $key => $plan) {
            $row = new SslPlan([
                'id' => null,
                'plan_key' => $key,
                'name' => $plan['name'] ?? $key,
                'tagline' => $plan['tagline'] ?? '',
                'badge' => $plan['badge'] ?? null,
                'monthly_price' => $plan['monthly'] ?? 0,
                'renewal_price' => $plan['renewal'] ?? $plan['monthly'] ?? 0,
                'discount_one_year' => $config['term_discount'][1] ?? 0,
                'discount_two_year' => $config['term_discount'][2] ?? 5,
                'discount_three_year' => $config['term_discount'][3] ?? 10,
            ]);

            $row->setRelation('features', collect(array_map(function ($feature) {
                // The config fallback has no visibility column: every row shows.
                return (object) ['feature' => $feature, 'is_available' => true, 'shown_to_customers' => true];
            }, (array) ($plan['features'] ?? []))));

            $plans[] = $this->resolve($row);
        }

        return $plans;
    }
}
