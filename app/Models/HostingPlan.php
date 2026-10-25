<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A hosting plan card, managed from /admin/hosting-plans.
 *
 * One row per card on the shared / VPS / dedicated hosting pages (the
 * dedicated page previously reused the shared data, so it keeps its own
 * identical rows seeded). `is_visible` hides a card without deleting it;
 * `sort` is the display order; the four price/term columns drive the billing
 * tabs — an inactive term renders as "Coming soon". The name is per billing
 * term too: `name` is the Monthly name and `name_yearly` / `name_two_year` /
 * `name_three_year` hold a term's own name (NULL = inherit `name`).
 *
 * Features live in hosting_plan_features (available/unavailable toggle per
 * plan) — no real FK, matching the rest of this schema.
 */
class HostingPlan extends Model
{
    const TYPE_SHARED = 'shared';
    const TYPE_VPS = 'vps';
    const TYPE_DEDICATED = 'dedicated';

    const TYPES = [self::TYPE_SHARED, self::TYPE_VPS, self::TYPE_DEDICATED];

    /** The five card circle styles rendered by the hosting pages. */
    const STYLE_CLASSES = [
        'plan-circle-basic' => 'Basic',
        'plan-circle-standard' => 'Standard',
        'plan-circle-advanced' => 'Advanced',
        'plan-circle-premium' => 'Premium',
        'plan-circle-enterprise' => 'Enterprise',
    ];

    protected $fillable = [
        'type', 'name', 'description', 'badge', 'style_class',
        'name_yearly', 'name_two_year', 'name_three_year',
        'price_monthly', 'price_yearly', 'price_two_year', 'price_three_year',
        'term_monthly_active', 'term_yearly_active', 'term_two_year_active', 'term_three_year_active',
        'term_monthly_visible', 'term_yearly_visible', 'term_two_year_visible', 'term_three_year_visible',
        'is_visible', 'sort',
    ];

    protected $casts = [
        'price_monthly' => 'float',
        'price_yearly' => 'float',
        'price_two_year' => 'float',
        'price_three_year' => 'float',
        'term_monthly_active' => 'boolean',
        'term_yearly_active' => 'boolean',
        'term_two_year_active' => 'boolean',
        'term_three_year_active' => 'boolean',
        'term_monthly_visible' => 'boolean',
        'term_yearly_visible' => 'boolean',
        'term_two_year_visible' => 'boolean',
        'term_three_year_visible' => 'boolean',
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * The card name shown for one billing term (period key: monthly /
     * yearly / twoYear / threeYear). Monthly IS the canonical `name`
     * column; the other terms use their own column once saved, and fall
     * back to it until then (same inherit rule as the feature rows).
     */
    public function nameFor($period)
    {
        if ($period === 'yearly') {
            return $this->name_yearly !== null && $this->name_yearly !== '' ? $this->name_yearly : $this->name;
        }
        if ($period === 'twoYear') {
            return $this->name_two_year !== null && $this->name_two_year !== '' ? $this->name_two_year : $this->name;
        }
        if ($period === 'threeYear') {
            return $this->name_three_year !== null && $this->name_three_year !== '' ? $this->name_three_year : $this->name;
        }

        return $this->name;
    }

    /** The name column a term's slug (yearly/two_year/three_year) saves into; null = the canonical `name`. */
    public static function termNameColumn($termSlug)
    {
        $map = [
            HostingPlanFeature::TERM_MONTHLY => null,
            HostingPlanFeature::TERM_YEARLY => 'name_yearly',
            HostingPlanFeature::TERM_TWO_YEAR => 'name_two_year',
            HostingPlanFeature::TERM_THREE_YEAR => 'name_three_year',
        ];

        return $map[$termSlug] ?? null;
    }

    public static function termVisibilityColumn($period)
    {
        $map = [
            'monthly' => 'term_monthly_visible',
            'yearly' => 'term_yearly_visible',
            'twoYear' => 'term_two_year_visible',
            'threeYear' => 'term_three_year_visible',
        ];

        return $map[$period] ?? 'term_monthly_visible';
    }

    public function visibleFor($period)
    {
        return (bool) $this->{self::termVisibilityColumn($period)};
    }

    public function features()
    {
        // Insertion order == display order (rows are re-created on save).
        return $this->hasMany(HostingPlanFeature::class)->orderBy('id');
    }
}
