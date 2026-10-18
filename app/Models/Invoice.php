<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A customer-facing billable document (INV-000001, INV-000002, ...).
 *
 * Created automatically beside domain orders, top-ups and later hosting
 * orders (`source_type`/`source_id`), or by an admin for manual/renewal
 * billing. Editing is admin-only; customers view and download PDFs.
 */
class Invoice extends Model
{
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_VOID = 'void';

    const TYPE_ORDER = 'order';
    const TYPE_RENEWAL = 'renewal';
    const TYPE_TOPUP = 'topup';
    const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'invoice_number', 'user_id', 'source_type', 'source_id', 'type', 'status',
        'payment_method', 'amount_paid', 'currency', 'subtotal', 'vat_percent',
        'vat_amount', 'total', 'due_date', 'paid_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'subtotal' => 'float',
        'vat_percent' => 'float',
        'vat_amount' => 'float',
        'total' => 'float',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sl_no');
    }

    /** Row produced the invoice (DomainOrder, Topup, ...), if any. */
    public function source()
    {
        return $this->morphTo(null, 'source_type', 'source_id');
    }

    /**
     * Next sequential invoice number. DB-agnostic: computed in PHP exactly
     * like User::generateUid() so MySQL and the SQLite test DB both work.
     */
    public static function generateNumber()
    {
        $prefix = 'INV';
        $max = 0;

        foreach (static::query()->pluck('invoice_number') as $number) {
            if (strpos((string) $number, $prefix . '-') === 0) {
                $max = max($max, (int) substr((string) $number, strlen($prefix) + 1));
            }
        }

        $next = $max + 1;

        do {
            $number = $prefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('invoice_number', $number)->exists());

        return $number;
    }

    /** Outstanding balance after payment(s) already received. */
    public function balanceDue()
    {
        return round(max(0, (float) $this->total - (float) $this->amount_paid), 2);
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isEditable()
    {
        return $this->status !== self::STATUS_VOID;
    }
}
