<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslPlan extends Model
{
    protected $fillable = [
        'plan_key', 'name', 'tagline', 'badge', 'monthly_price', 'renewal_price',
        'discount_one_year', 'discount_two_year', 'discount_three_year',
        'term_one_year_active', 'term_two_year_active', 'term_three_year_active',
        'term_one_year_visible', 'term_two_year_visible', 'term_three_year_visible',
        'is_visible', 'sort',
    ];

    protected $casts = [
        'monthly_price' => 'float',
        'renewal_price' => 'float',
        'discount_one_year' => 'float',
        'discount_two_year' => 'float',
        'discount_three_year' => 'float',
        'term_one_year_active' => 'boolean',
        'term_two_year_active' => 'boolean',
        'term_three_year_active' => 'boolean',
        'term_one_year_visible' => 'boolean',
        'term_two_year_visible' => 'boolean',
        'term_three_year_visible' => 'boolean',
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

    public function features()
    {
        return $this->hasMany(SslPlanFeature::class)->orderBy('sort')->orderBy('id');
    }

    public function termActive($years)
    {
        return (bool) $this->{'term_' . $this->termSlug($years) . '_active'};
    }

    public function termVisible($years)
    {
        return (bool) $this->{'term_' . $this->termSlug($years) . '_visible'};
    }

    public function discountFor($years)
    {
        return (float) $this->{'discount_' . $this->termSlug($years)};
    }

    private function termSlug($years)
    {
        return [
            1 => 'one_year',
            2 => 'two_year',
            3 => 'three_year',
        ][(int) $years] ?? 'one_year';
    }
}
