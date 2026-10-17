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
        'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'sl_no' => 'integer',
        'years' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'float',
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
