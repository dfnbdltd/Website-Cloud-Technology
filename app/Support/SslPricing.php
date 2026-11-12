<?php

namespace App\Support;

/**
 * SSL plan prices, formatted for display in BDT.
 *
 * The plans themselves live in config/ssl_plans.php so marketing can change a
 * price without touching code. This class owns the arithmetic so the number on
 * the card, the number in the term dropdown and the number in the totals line
 * can never disagree:
 *
 *   term total  = monthly × 12 × years, minus the term discount
 *   per month   = ? (a rounded whole taka, so the price never reads ৳71.99)
 *   savings     = (monthly × 12 × years) − term total
 *
 * Every money value returned here is a float in BDT; the Blade partial decides
 * whether it prints as ৳79, ৳1,899 or ৳71.91. No rounding happens twice.
 */
class SslPricing
{
    /** Months in a billing year — the "per month" divisor. */
    const MONTHS_PER_YEAR = 12;

    /**
     * The four plans, price maths already resolved.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function plans()
    {
        $config = config('ssl_plans', []);
        $plans = isset($config['plans']) && is_array($config['plans']) ? $config['plans'] : [];
        $defaultTerm = (int) ($config['default_term'] ?? 1);
        $termLabels = self::termLabels();
        $discounts = isset($config['term_discount']) && is_array($config['term_discount'])
            ? $config['term_discount']
            : [];

        $resolved = [];

        foreach ($plans as $key => $plan) {
            $monthly = (float) ($plan['monthly'] ?? 0);
            $renewal = (float) ($plan['renewal'] ?? $monthly);

            $terms = [];
            foreach ($termLabels as $years => $label) {
                $discount = (float) ($discounts[$years] ?? 0);
                $full = $monthly * self::MONTHS_PER_YEAR * $years;
                // Whole taka: a BDT certificate price never reads ৳2,559.60.
                $total = round($full * (1 - ($discount / 100)));

                $terms[$years] = [
                    'years' => $years,
                    'label' => $label,
                    'discount' => $discount,
                    'total' => $total,
                    'savings' => max(0, round($full) - $total),
                    // Effective monthly for this term, rounded to a whole taka
                    // so the big price line stays short and calm.
                    'per_month' => round($total / ($years * self::MONTHS_PER_YEAR)),
                    'per_year' => round($total / $years),
                ];
            }

            $default = isset($terms[$defaultTerm]) ? $terms[$defaultTerm] : reset($terms);

            $resolved[] = [
                'key' => (string) $key,
                'name' => (string) ($plan['name'] ?? $key),
                'tagline' => (string) ($plan['tagline'] ?? ''),
                'badge' => isset($plan['badge']) ? (string) $plan['badge'] : null,
                'buy_route' => isset($plan['buy_route']) ? $plan['buy_route'] : null,
                'monthly' => $monthly,
                'renewal' => $renewal,
                'per_year' => $default['per_year'],
                'renewal_year' => round($renewal * self::MONTHS_PER_YEAR),
                'features' => array_values((array) ($plan['features'] ?? [])),
                'terms' => $terms,
                'default' => $default,
            ];
        }

        return $resolved;
    }

    /**
     * Term labels keyed by number of years (1 => '1 Year', ...).
     *
     * @return array<int, string>
     */
    public static function termLabels()
    {
        $terms = config('ssl_plans.terms', [1 => '1 Year']);
        $labels = [];

        foreach ((array) $terms as $years => $label) {
            $labels[(int) $years] = (string) $label;
        }

        return $labels;
    }

    /**
     * Does any plan carry a discount on a term longer than a year? Used by the
     * view to decide whether the savings line is worth printing at all.
     *
     * @param  array<int, array<string, mixed>>  $plans
     * @return bool
     */
    public static function hasTermDiscounts(array $plans)
    {
        foreach ($plans as $plan) {
            foreach ($plan['terms'] as $term) {
                if ($term['discount'] > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Bangladeshi-grouped BDT amount, e.g. 1299 => "1,299".
     * The ৳ glyph is added by the view so it can be styled on its own.
     *
     * @param  float|int  $amount
     * @return string
     */
    public static function money($amount)
    {
        $amount = (float) $amount;
        $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;

        return number_format($amount, $decimals);
    }

    /**
     * A "per month" figure: whole taka when it is close to one, otherwise two
     * decimals (৳79 / ৳71.91). Keeps the big price line short and calm.
     *
     * @param  float|int  $amount
     * @return string
     */
    public static function perMonth($amount)
    {
        return self::money($amount);
    }
}
