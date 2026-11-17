<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One SSL certificate order (see the ssl_orders migration for the lifecycle).
 */
class SslOrder extends Model
{
    const STATUS_PAYMENT_PENDING = 'payment_pending';
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ACTIVE = 'active';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'reference', 'user_id', 'placed_by_user_id',
        'ssl_plan_id', 'plan_key', 'plan_name', 'resellerclub_plan_id',
        'domain', 'years',
        'total', 'purchase_price', 'exchange_rate', 'price_source',
        'status', 'payment_method', 'payment_status', 'provider', 'provider_payment_id',
        'customer_name', 'customer_email', 'customer_phone',
        'reseller_order_id', 'registration_status',
    ];

    protected $casts = [
        'years' => 'integer',
        'total' => 'float',
        'purchase_price' => 'float',
        'exchange_rate' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }
}
