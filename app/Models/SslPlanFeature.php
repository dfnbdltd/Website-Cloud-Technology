<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslPlanFeature extends Model
{
    protected $fillable = ['ssl_plan_id', 'feature', 'is_available', 'shown_to_customers', 'sort'];

    protected $casts = [
        'ssl_plan_id' => 'integer',
        'is_available' => 'boolean',
        'shown_to_customers' => 'boolean',
        'sort' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SslPlan::class, 'ssl_plan_id');
    }

    /**
     * Is this feature meant to appear on the customer card at all?
     *
     * Falls back to `is_available` when the column has not been migrated yet,
     * so the views work on an install that is still one `migrate` behind.
     *
     * @return bool
     */
    public function isShownToCustomers()
    {
        if (array_key_exists('shown_to_customers', $this->attributes)
            && $this->attributes['shown_to_customers'] !== null) {
            return (bool) $this->attributes['shown_to_customers'];
        }

        return (bool) $this->is_available;
    }
}
