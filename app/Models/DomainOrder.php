<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrder extends Model
{
    protected $fillable = ['reference', 'user_id', 'total', 'status', 'payment_method', 'payment_status', 'provider', 'provider_payment_id', 'customer_name', 'customer_phone', 'customer_email', 'payload'];
    protected $casts = ['total' => 'float'];

    public function items() { return $this->hasMany(DomainOrderItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}