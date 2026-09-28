<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['domain_order_id', 'reference', 'provider', 'provider_payment_id', 'amount', 'status', 'raw_response'];
    protected $casts = ['amount' => 'float'];
}