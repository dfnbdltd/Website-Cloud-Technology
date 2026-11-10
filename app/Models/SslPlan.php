<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One SSL certificate plan.
 *
 * All three billing terms (1, 2 and 3 years) are always offered; the only
 * per-term setting is a discount percentage. Term totals are derived, never
 * stored — see App\Services\SslPlanService::resolve().
 */
class SslPlan extends Model
{
    protected $fillable = [
        'plan_key', 'name', 'tagline', 'badge', 'monthly_price', 'renewal_price',
        'discount_one_year', 'discount_two_year', 'discount_three_year',
        'is_visible', 'sort',
    ];

    protected $casts = [
        'monthly_price' => 'float',
        'renewal_price' => 'float',
        'discount_one_year' => 'float',
        'discount_two_year' => 'float',
        'discount_three_year' => 'float',
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

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
