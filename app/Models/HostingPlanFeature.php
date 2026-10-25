<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One feature row of a hosting plan card — per billing term.
 *
 * A card keeps its own row set per billing term (`billing_term`: monthly /
 * yearly / two_year / three_year, ordered by id = inserted order). A term
 * without rows inherits the Monthly set at read time.
 *
 * `feature_group` is `primary` (the 5 row list) or `extra` (behind
 * "See more"). `is_available` drives the icon: true renders the check icon,
 * false renders the red cross (`feature-unavailable` CSS class).
 * `feature_key` preserves `data-feature="ecommerce"` used by the shared
 * page's period-toggle JS. `value_monthly` holds this row's full line text
 * ("300 GB bandwidth/month") — for every term.
 */
class HostingPlanFeature extends Model
{
    const GROUP_PRIMARY = 'primary';
    const GROUP_EXTRA = 'extra';

    const TERM_MONTHLY = 'monthly';
    const TERM_YEARLY = 'yearly';
    const TERM_TWO_YEAR = 'two_year';
    const TERM_THREE_YEAR = 'three_year';

    /** DB slug for a section period key (twoYear -> two_year). */
    public static function termSlug($period)
    {
        $map = [
            'monthly' => self::TERM_MONTHLY,
            'yearly' => self::TERM_YEARLY,
            'twoYear' => self::TERM_TWO_YEAR,
            'threeYear' => self::TERM_THREE_YEAR,
        ];

        return $map[$period] ?? self::TERM_MONTHLY;
    }

    protected $fillable = [
        'hosting_plan_id', 'billing_term', 'feature_group', 'name',
        'value_monthly', 'value_yearly', 'feature_key', 'is_available',
    ];

    protected $casts = [
        'hosting_plan_id' => 'integer',
        'is_available' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }
}
