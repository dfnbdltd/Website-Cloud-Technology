<?php

namespace App\Services;

use App\Models\SslPlan;
use Illuminate\Support\Facades\Schema;

class SslPlanService
{
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

    public function resolve(SslPlan $plan)
    {
        $terms = [];
        foreach ([1 => '1 Year', 2 => '2 Years', 3 => '3 Years'] as $years => $label) {
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
            'active' => [1 => $plan->termActive(1), 2 => $plan->termActive(2), 3 => $plan->termActive(3)],
            'visible' => [1 => $plan->termVisible(1), 2 => $plan->termVisible(2), 3 => $plan->termVisible(3)],
            'default' => $this->defaultTerm($plan, $terms),
        ];
    }

    private function defaultTerm(SslPlan $plan, array $terms)
    {
        foreach ([1, 2, 3] as $years) {
            if ($plan->termActive($years) && $plan->termVisible($years)) {
                return $terms[$years];
            }
        }

        return $terms[1];
    }

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
