<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        'plan_specs', 'admin_seen_at', 'hosted_by_admin_id', 'hosted_by_admin_email',
    ];

    protected $casts = [
        'months' => 'integer',
        'total' => 'float',
        'paid_at' => 'datetime',
        'hosted_at' => 'datetime',
        'admin_seen_at' => 'datetime',
    ];

    /** Cached "does hosting_orders have this column yet?" so code works before the SQL is run. */
    public static function columnExists($column)
    {
        static $known = [];

        // Only a "yes" is remembered, so running the SQL takes effect at once.
        if (!empty($known[$column])) {
            return true;
        }

        try {
            return $known[$column] = Schema::hasColumn('hosting_orders', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Red badge on the admin sidebar: PAID, not-cancelled hosting orders no admin
     * has opened yet. Never throws; 0 when the column is not there yet.
     */
    public static function adminUnseenCount()
    {
        if (!self::columnExists('admin_seen_at')) {
            return 0;
        }

        try {
            return (int) self::where('payment_status', 'paid')
                ->where('status', '!=', self::STATUS_CANCELLED)
                ->whereNull('admin_seen_at')
                ->count();
        } catch (\Throwable $e) {
            \Log::warning('Admin hosting badge count failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * "Plan A (10 GB | NVMe Storage)" from the snapshot saved at purchase.
     * Old orders with no snapshot show the plan name only.
     */
    public function planLabel()
    {
        return self::formatPlanLabel($this->plan_name, $this->plan_specs);
    }

    /** Single place that joins a plan name and its spec text. */
    public static function formatPlanLabel($name, $specs)
    {
        $name = trim((string) $name);
        $specs = trim((string) $specs);

        return $specs === '' ? $name : $name . ' (' . $specs . ')';
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
