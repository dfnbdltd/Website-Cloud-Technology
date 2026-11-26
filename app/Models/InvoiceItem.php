<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One billable line of an invoice. Domain/renewal lines carry `years`
 * (e.g. the customer extended a domain from 3 to 4) and one-off/manual
 * lines leave `years` at 0 and bill per unit (`quantity`). Feeds the
 * server-computed line_total:
 *
 *   line_total = quantity × (years > 0 ? years : 1) × unit_price
 */
class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'sl_no', 'description', 'item_type', 'years',
        'quantity', 'unit_price', 'purchase_price', 'line_total', 'period_label',
    ];

    /** Cached: does invoice_items.period_label exist yet (database/hosting_flow_phase0.sql)? */
    public static function hasPeriodLabelColumn()
    {
        static $has = false;

        // Only a "yes" is remembered, so running the SQL takes effect at once.
        if (!$has) {
            try {
                $has = \Illuminate\Support\Facades\Schema::hasColumn('invoice_items', 'period_label');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return $has;
    }

    /** "Monthly", "1 Year", "2 Years", "3 Years" for a hosting order's term in months. */
    public static function periodLabelForMonths($months, $fallback = null)
    {
        $labels = [1 => 'Monthly', 12 => '1 Year', 24 => '2 Years', 36 => '3 Years'];

        return $labels[(int) $months] ?? $fallback;
    }

    /** What the Years/Term column shows: the term label where set, else the years number, else a dash. */
    public function termDisplay()
    {
        $label = trim((string) $this->period_label);

        return $label !== '' ? $label : ($this->years ?: '—');
    }

    protected $casts = [
        'sl_no' => 'integer',
        'years' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'float',
        'purchase_price' => 'float',
        'line_total' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Authoritative line math; called by the invoice editing service. */
    public function computeLineTotal()
    {
        $quantity = max(1, (int) $this->quantity);
        $years = max(0, (int) $this->years);
        // years = 0 marks a one-off/manual line: bill per unit once.
        $multiplier = $years > 0 ? $years : 1;

        return round($quantity * $multiplier * (float) $this->unit_price, 2);
    }
}
