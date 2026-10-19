<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrderItem extends Model
{
    protected $fillable = ['domain_order_id', 'domain', 'tld', 'years', 'price', 'renewal_price', 'purchase_price'];
    protected $casts = ['price' => 'float', 'renewal_price' => 'float', 'purchase_price' => 'float'];
}