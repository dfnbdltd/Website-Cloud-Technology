<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Customer side of the invoice system: every invoice is VIEWED (hosted page)
 * and DOWNLOADED (PDF) here. Editing is admin-only (AdminController).
 *
 * Ownership rule: admins may open anything; customers may only open invoices
 * whose user_id matches the id stored in session('customer').
 */
class InvoiceController extends Controller
{
    /**
     * Hosted invoice page (also the printable / email link target).
     */
    public function show(Request $request, Invoice $invoice)
    {
        if (!$this->canOpen($request, $invoice)) {
            return redirect()->route('invoices')->withErrors(['invoice' => 'Invoice not found in this account.']);
        }

        return view('invoices.show', [
            'invoice' => $invoice->loadMissing('items', 'user'),
            'isAdmin' => false,
        ]);
    }

    /**
     * PDF download — streams INV-000001.pdf (download=1 forces the save dialog).
     *
     * Admins receive the internal customer-information sheet with purchase
     * price and profit; customers receive the same layout without cost data.
     */
    public function pdf(Request $request, Invoice $invoice)
    {
        if (!$this->canOpen($request, $invoice)) {
            return redirect()->route('invoices')->withErrors(['invoice' => 'Invoice not found in this account.']);
        }

        try {
            $bytes = app(\App\Services\InvoicePdf::class)->render(
                $invoice,
                (bool) $request->session()->get('admin')
            );
        } catch (\Throwable $e) {
            Log::error('Invoice PDF render failed', ['invoice' => $invoice->invoice_number, 'error' => $e->getMessage()]);
            return back()->withErrors(['invoice' => 'We could not generate the PDF right now. Please try again later.']);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }

    private function canOpen(Request $request, Invoice $invoice)
    {
        if ($request->session()->get('admin')) {
            return true;
        }

        $customer = $request->session()->get('customer');

        return $customer && $invoice->user_id && (int) $invoice->user_id === (int) $customer['id'];
    }
}
