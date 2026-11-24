<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * One hosting plan order (see the hosting_orders migration for the lifecycle).
 */
class HostingOrder extends Model
{
    const STATUS_PAYMENT_PENDING = 'payment_pending';
    const STATUS_PAID = 'paid';
    /** Payment received; waiting for the hosting account to be set up. */
    const STATUS_AWAITING_SETUP = 'awaiting_setup';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ACTIVE = 'active';
    /** The admin set the hosting up on the server and pressed "Mark as hosted". */
    const STATUS_HOSTED = 'hosted';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference', 'user_id', 'placed_by_user_id',
        'region', 'type', 'plan_id', 'plan_name', 'product',
        'period', 'period_label', 'months', 'domain', 'total',
        'status', 'payment_method', 'payment_status', 'provider', 'provider_payment_id',
        'payment_response', 'paid_at', 'hosted_at', 'admin_note',
        'customer_name', 'customer_email', 'customer_phone',
    ];

    protected $casts = [
        'months' => 'integer',
        'total' => 'float',
        'paid_at' => 'datetime',
        'hosted_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isHosted()
    {
        return $this->status === self::STATUS_HOSTED || $this->status === self::STATUS_ACTIVE;
    }

    /** True while a paid order is still waiting for / going through setup. */
    public function isSettingUp()
    {
        return $this->isPaid()
            && in_array($this->status, [self::STATUS_PAID, self::STATUS_AWAITING_SETUP, self::STATUS_PROCESSING], true);
    }

    /** What the customer sees: Waiting for payment | Awaiting setup | Setting up | Hosted | Cancelled. */
    public function statusLabel()
    {
        if ($this->isCancelled()) {
            return 'Cancelled';
        }
        if ($this->isHosted()) {
            return 'Hosted';
        }
        if ($this->status === self::STATUS_PROCESSING) {
            return 'Setting up';
        }
        if ($this->isPaid()) {
            return 'Awaiting setup';
        }

        return 'Waiting for payment';
    }

    /** Bought by someone for another customer's account. */
    public function isForCustomer()
    {
        return $this->placed_by_user_id && (int) $this->placed_by_user_id !== (int) $this->user_id;
    }
}
