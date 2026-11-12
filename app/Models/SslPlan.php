<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One SSL certificate plan.
 *
 * Two pricing modes:
 *
 *  live    — `resellerclub_plan_id` is set and ResellerClub returned a selling
 *            price, so the card shows the live API price converted to BDT.
 *  typed   — no link (or the API is unavailable): the BDT columns here are the
 *            price, exactly as before live pricing existed.
 *
 * In live mode the API supplies the terms it actually sells (12 and 24 months),
 * so the three discount percentages below apply only to the typed fallback.
 * Term totals are always derived, never stored — see
 * App\Services\SslPlanService::resolve().
 */
class SslPlan extends Model
{
    protected $fillable = [
        'plan_key', 'name', 'tagline', 'badge', 'resellerclub_plan_id',
        'monthly_price', 'renewal_price',
        'discount_one_year', 'discount_two_year', 'discount_three_year',
        'is_visible', 'sort',
    ];

    protected $casts = [
        // Nullable: null means "not linked", so it must not cast to 0.
        'resellerclub_plan_id' => 'integer',
        'monthly_price' => 'float',
        'renewal_price' => 'float',
        'discount_one_year' => 'float',
        'discount_two_year' => 'float',
        'discount_three_year' => 'float',
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * The ResellerClub product ID, or null when this plan is priced by hand.
     *
     * Read through this accessor rather than the attribute so an install that
     * has not run the 2026_11_13 migration yet (no column) still reports
     * "not linked" instead of a noisy Eloquent missing-attribute path.
     *
     * @return int|null
     */
    public function resellerclubPlanId()
    {
        if (!array_key_exists('resellerclub_plan_id', $this->attributes)) {
            return null;
        }

        $id = $this->attributes['resellerclub_plan_id'];

        return $id === null || $id === '' ? null : (int) $id;
    }

    public function features()
    {
        return $this->hasMany(SslPlanFeature::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Discount percentage for a term. Unknown terms fall back to the 1-year
     * value so a bad number can never produce a negative price.
     */
    public function discountFor($years)
    {
        $column = [
            1 => 'discount_one_year',
            2 => 'discount_two_year',
            3 => 'discount_three_year',
        ][(int) $years] ?? 'discount_one_year';

        return (float) $this->{$column};
    }
}
