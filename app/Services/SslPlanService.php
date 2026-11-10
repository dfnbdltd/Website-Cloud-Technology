<?php

namespace App\Services;

use App\Models\SslPlan;
use Illuminate\Support\Facades\Schema;

/**
 * Reads SSL plans for the public / client cards.
 *
 * Source of truth: the ssl_plans / ssl_plan_features tables. When those are
 * absent or empty (e.g. a fresh checkout before `php artisan migrate`) this
 * falls back to config/ssl_plans.php so the page is never blank.
 */
class SslPlanService
{
    /** Billing terms offered for every plan, in display order. */
    const TERMS = [1 => '1 Year', 2 => '2 Years', 3 => '3 Years'];

    private static $tablesMissing;

    public function plans()
    {
        if (!$this->isManaged()) {
            return $this->legacy();
        }

        return SslPlan::query()
            ->where('is_visible', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->with('features')
            ->get()
            ->map(function (SslPlan $plan) {
                return $this->resolve($plan);
            })
            ->all();
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
     * Every plan offers all three terms; only the discount varies. `total` is
     * rounded to whole taka so a BDT price never reads ৳2,559.60, and
     * `per_month` is the effective monthly for that term.
     */
    public function resolve(SslPlan $plan)
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
            'features' => $plan->features->map(function ($feature) {
                return [
                    'text' => $feature->feature,
                    'available' => (bool) $feature->is_available,
                ];
            })->values()->all(),
            'terms' => $terms,
            // 1 year is what the card quotes by default.
            'default' => $terms[1],
        ];
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
                return (object) ['feature' => $feature, 'is_available' => true];
            }, (array) ($plan['features'] ?? []))));

            $plans[] = $this->resolve($row);
        }

        return $plans;
    }
}
