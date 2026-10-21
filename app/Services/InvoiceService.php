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
                    'purchase_price' => (float) ($item->purchase_price ?? $item->price),
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
     * amount preserved, so the customer only owes the difference. Manually
     * choosing unpaid on an unchanged paid invoice clears the payment instead.
     */
    public function applyEdit(Invoice $invoice, array $lines, array $fields): Invoice
    {
        return DB::transaction(function () use ($invoice, $lines, $fields) {
            $paidBefore = (float) $invoice->amount_paid;
            $wasPaid = $invoice->status === Invoice::STATUS_PAID;
            $existingItems = $invoice->items()->get()->keyBy('id');

            $invoice->items()->delete();
            foreach (array_values($lines) as $index => $line) {
                $existingItem = !empty($line['id'])
                    ? $existingItems->get((int) $line['id'])
                    : $existingItems->firstWhere('description', $line['description']);
                $item = new InvoiceItem([
                    'sl_no' => $index + 1,
                    'description' => $line['description'],
                    'item_type' => $line['item_type'] ?? null,
                    'years' => (int) ($line['years'] ?? 0),
                    'quantity' => (int) ($line['quantity'] ?? 1),
                    'unit_price' => (float) $line['unit_price'],
                    'purchase_price' => $existingItem ? $existingItem->purchase_price : null,
                ]);
                $item->line_total = $item->computeLineTotal();
                $invoice->items()->create($item->getAttributes());
            }

            // array_key_exists (not isset) so an explicit empty value clears the field:
            // payment method, due date and notes are all optional on the admin form.
            $invoice->fill([
                'payment_method' => array_key_exists('payment_method', $fields)
                    ? ($fields['payment_method'] ?: null)
                    : $invoice->payment_method,
                'vat_percent' => array_key_exists('vat_percent', $fields)
                    ? (float) $fields['vat_percent']
                    : $invoice->vat_percent,
                'due_date' => array_key_exists('due_date', $fields)
                    ? ($fields['due_date'] ?: null)
                    : $invoice->due_date,
                'notes' => array_key_exists('notes', $fields)
                    ? (trim((string) $fields['notes']) !== '' ? trim((string) $fields['notes']) : null)
                    : $invoice->notes,
            ]);
            $this->recalculate($invoice);

            // Growth rule wins: a paid invoice whose total grew (3 -> 4 years)
            // reopens for the difference. Otherwise the admin's explicit
            // paid/unpaid choice is respected — including undoing a payment.
            $grewWhilePaid = $wasPaid && (float) $invoice->total > $paidBefore;

            if ($fields['status'] === Invoice::STATUS_VOID) {
                $nextStatus = Invoice::STATUS_VOID;
            } elseif ($grewWhilePaid) {
                $nextStatus = Invoice::STATUS_UNPAID;
            } else {
                $nextStatus = $fields['status'];
            }

            if ($nextStatus === Invoice::STATUS_VOID) {
                $invoice->fill([
                    'status' => Invoice::STATUS_VOID,
                    'paid_at' => $invoice->paid_at,
                ]);
            } elseif ($nextStatus === Invoice::STATUS_UNPAID) {
                // Manual "mark unpaid" on a paid invoice reverses the payment, so
                // the full total is due again. The growth rule keeps the received
                // amount on record and reopens only the difference.
                $invoice->fill([
                    'status' => Invoice::STATUS_UNPAID,
                    'amount_paid' => $grewWhilePaid || !$wasPaid ? $paidBefore : 0,
                    'paid_at' => null,
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

    /**
     * Quick admin status switch from the hosted invoice page. Marking paid
     * records the full total as received; marking unpaid reverses the payment
     * so the whole total is due again.
     */
    public function setStatus(Invoice $invoice, string $status): Invoice
    {
        if ($status === Invoice::STATUS_PAID) {
            $invoice->forceFill([
                'status' => Invoice::STATUS_PAID,
                'amount_paid' => (float) $invoice->total,
                'paid_at' => now(),
            ])->save();
        } else {
            $invoice->forceFill([
                'status' => Invoice::STATUS_UNPAID,
                'amount_paid' => 0,
                'paid_at' => null,
            ])->save();
        }

        return $invoice;
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
