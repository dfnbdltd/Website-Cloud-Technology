<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DomainOrder extends Model
{
    protected $fillable = ['reference', 'user_id', 'placed_by_user_id', 'total', 'status', 'payment_method', 'payment_status', 'provider', 'provider_payment_id', 'customer_name', 'customer_phone', 'customer_email', 'payload'];
    protected $casts = ['total' => 'float'];

    /** @var bool|null */
    private static $placedByColumn;

    public function items() { return $this->hasMany(DomainOrderItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    /**
     * Does domain_orders have the placed_by_user_id column yet?
     *
     * The column is added with database/domain_orders_placed_by.sql, run by hand.
     * Until it exists, ordering must keep working exactly as before (it just
     * cannot remember who placed an order for someone else), so every use of
     * the column goes through this check.
     */
    public static function hasPlacedByColumn()
    {
        if (self::$placedByColumn === null) {
            self::$placedByColumn = Schema::hasColumn('domain_orders', 'placed_by_user_id');
        }

        return self::$placedByColumn;
    }

    /** Forget the remembered answer (tests, or right after the column is added). */
    public static function resetPlacedByCache()
    {
        self::$placedByColumn = null;
    }

    /**
     * Orders a person may open: the ones they own, plus (when the column
     * exists) the ones they placed for another customer.
     */
    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('user_id', $userId);
            if (self::hasPlacedByColumn()) {
                $query->orWhere('placed_by_user_id', $userId);
            }
        });
    }

    public function isVisibleTo($userId)
    {
        if ((int) $this->user_id === (int) $userId) {
            return true;
        }

        return self::hasPlacedByColumn() && (int) $this->placed_by_user_id === (int) $userId;
    }
}
