<?php

namespace App\Services;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Topup;
use Illuminate\Support\Facades\DB;

/**
 * The only code allowed to create, flip or edit invoices. Existing flows
 * (order placement, admin payment confirmation, top-up approval) call in
 * here with one line — their own behaviour is untouched.
 *
 * Money math is always recomputed server-side from stored line data;
 * posted totals are never trusted (same stance as the domain cart).
 */
class InvoiceService
{
    /**
     * Create (or return the already-created) invoice for a domain order.
     * Safe to call right after order persistence or from the backfill.
     */
    public function createForOrder(DomainOrder $order): ?Invoice
    {
        if (!$order->exists) {
            return null;
        }

        $existing = $this->findForSource(DomainOrder::class, $order->id);
        if ($existing) {
            return $existing;
        }

        $paid = $order->payment_status === 'paid' || $order->status === 'paid';

        return DB::transaction(function () use ($order, $paid) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateNumber(),
                'user_id' => $order->user_id,
                'source_type' => DomainOrder::class,
                'source_id' => $order->id,
                'type' => Invoice::TYPE_ORDER,
                'status' => $paid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                'payment_method' => $order->payment_method,
                'amount_paid' => $paid ? (float) $order->total : 0,
                'currency' => 'BDT',
                'vat_percent' => 0,
                'paid_at' => $paid ? now() : null,
                'created_by' => $paid ? 'system' : null,
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'sl_no' => $invoice->items()->count() + 1,
                    'description' => 'Domain registration — ' . $item->domain,
                    'item_type' => 'domain-registration',
                    'years' => (int) $item->years,
                    'quantity' => 1,
                    'unit_price' => (float) $item->price,
                    'line_total' => round((float) $item->price * (int) $item->years, 2),
                ]);
            }

            $this->recalculate($invoice);

            return $invoice;
        });
    }

    /**
     * Paid receipt for an approved wallet top-up (instant bKash payments
     * run through the same path when the admin-side approval is skipped:
     * WalletController credits immediately, so this is called there too).
     */
    public function createForTopup(Topup $topup): ?Invoice
    {
        if (!$topup->exists) {
            return null;
        }

        $existing = $this->findForSource(Topup::class, $topup->id);
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($topup) {
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateNumber(),
                'user_id' => $topup->user_id,
                'source_type' => Topup::class,
                'source_id' => $topup->id,
                'type' => Invoice::TYPE_TOPUP,
                'status' => Invoice::STATUS_PAID,
                'payment_method' => $topup->method,
                'amount_paid' => (float) $topup->amount,
                'currency' => 'BDT',
                'vat_percent' => 0,
                'paid_at' => $topup->approved_at ?: now(),
                'created_by' => 'system',
            ]);

            $invoice->items()->create([
                'sl_no' => 1,
                'description' => 'Wallet balance top-up (' . ucfirst((string) $topup->method) . ')',
                'item_type' => 'topup',
                'years' => 0,
                'quantity' => 1,
                'unit_price' => (float) $topup->amount,
                'line_total' => (float) $topup->amount,
            ]);

            $this->recalculate($invoice);

            return $invoice;
        });
    }

    /** Flip the linked invoice to paid when the source order is paid. */
    public function markOrderPaid(DomainOrder $order): void
    {
        $invoice = $this->findForSource(DomainOrder::class, $order->id);
        if (!$invoice || $invoice->status === Invoice::STATUS_PAID || $invoice->status === Invoice::STATUS_VOID) {
            return;
        }

        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => $invoice->amount_paid > 0 ? $invoice->amount_paid : (float) $invoice->total,
            'paid_at' => now(),
        ])->save();
    }

    /** Unlock the invoice again if a paid order is refunded/failed. */
    public function markOrderUnpaid(DomainOrder $order): void
    {
        $invoice = $this->findForSource(DomainOrder::class, $order->id);
        if (!$invoice || $invoice->status !== Invoice::STATUS_PAID) {
            return;
        }

        $invoice->forceFill([
            'status' => self::unpaidUnlessManuallyChanged($invoice),
            'amount_paid' => 0,
            'paid_at' => null,
        ])->save();
    }

    private static function unpaidUnlessManuallyChanged(Invoice $invoice)
    {
        return $invoice->status === Invoice::STATUS_PAID && $invoice->created_by === 'system'
            ? Invoice::STATUS_UNPAID
            : $invoice->status;
    }

    /**
     * Admin edit: replace all line items and supporting fields atomically,
     * renumber SL 1..n and recompute the money. A paid invoice whose total
     * just grew (e.g. 3 years -> 4) reopens as unpaid with the already-paid
     * amount preserved, so the customer only owes the difference.
     */
    public function applyEdit(Invoice $invoice, array $lines, array $fields): Invoice
    {
        return DB::transaction(function () use ($invoice, $lines, $fields) {
            $paidBefore = (float) $invoice->amount_paid;
            $wasPaid = $invoice->status === Invoice::STATUS_PAID;

            $invoice->items()->delete();
            foreach (array_values($lines) as $index => $line) {
                $item = new InvoiceItem([
                    'sl_no' => $index + 1,
                    'description' => $line['description'],
                    'item_type' => $line['item_type'] ?? null,
                    'years' => (int) ($line['years'] ?? 0),
                    'quantity' => (int) ($line['quantity'] ?? 1),
                    'unit_price' => (float) $line['unit_price'],
                ]);
                $item->line_total = $item->computeLineTotal();
                $invoice->items()->create($item->getAttributes());
            }

            $invoice->fill([
                'payment_method' => isset($fields['payment_method']) && $fields['payment_method'] !== null
                    ? $fields['payment_method']
                    : $invoice->payment_method,
                'vat_percent' => isset($fields['vat_percent']) ? (float) $fields['vat_percent'] : $invoice->vat_percent,
                'due_date' => isset($fields['due_date']) ? $fields['due_date'] : $invoice->due_date,
                'notes' => isset($fields['notes']) && trim((string) $fields['notes']) !== ''
                    ? trim((string) $fields['notes'])
                    : $invoice->notes,
            ]);
            $this->recalculate($invoice);

            $nextStatus = $fields['status'] === Invoice::STATUS_VOID
                ? Invoice::STATUS_VOID
                : ($wasPaid && (float) $invoice->total > $paidBefore
                    ? Invoice::STATUS_UNPAID
                    : ($fields['status'] === Invoice::STATUS_PAID ? Invoice::STATUS_PAID : $invoice->status));

            if ($nextStatus === Invoice::STATUS_VOID || $nextStatus === Invoice::STATUS_UNPAID) {
                // Money already received stays on record; reopening keeps the paid amount.
                $invoice->fill([
                    'status' => $nextStatus,
                    'amount_paid' => $nextStatus === Invoice::STATUS_UNPAID ? $paidBefore : $invoice->amount_paid,
                    'paid_at' => $nextStatus === Invoice::STATUS_VOID ? $invoice->paid_at : null,
                ]);
            } elseif ($nextStatus === Invoice::STATUS_PAID && !$wasPaid) {
                $invoice->fill([
                    'status' => Invoice::STATUS_PAID,
                    'amount_paid' => (float) $invoice->total,
                    'paid_at' => now(),
                ]);
            }

            $invoice->save();

            return $invoice;
        });
    }

    /** Recompute subtotal/VAT/total from the stored line items. */
    public function recalculate(Invoice $invoice): void
    {
        $subtotal = round((float) $invoice->items()->sum('line_total'), 2);
        $vat = round($subtotal * (float) $invoice->vat_percent / 100, 2);

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'vat_percent' => (float) $invoice->vat_percent,
            'vat_amount' => $vat,
            'total' => round($subtotal + $vat, 2),
        ])->save();
    }

    public function findForSource($type, $id): ?Invoice
    {
        return Invoice::where('source_type', $type)->where('source_id', $id)->first();
    }
}
