<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslPlanFeature extends Model
{
    protected $fillable = ['ssl_plan_id', 'feature', 'is_available', 'sort'];

    protected $casts = [
        'ssl_plan_id' => 'integer',
        'is_available' => 'boolean',
        'sort' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(SslPlan::class, 'ssl_plan_id');
    }
}
