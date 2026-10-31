<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Renders invoices as PDF with mPDF (pure PHP — no external binary).
 * Customer invoices use the portrait invoice sheet that mirrors the
 * on-site design; admins get the landscape customer-information report.
 *
 * The same document adapts to its viewer: admins get the internal
 * sheet (purchase/profit columns), customers get the invoice sheet.
 */
class InvoicePdf
{
    public function render(Invoice $invoice, bool $isAdmin = false): ?string
    {
        $tempDir = storage_path('framework/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $html = view('invoices.pdf', [
            'invoice' => $invoice->loadMissing('items', 'user', 'source'),
            'isAdmin' => $isAdmin,
        ])->render();

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            // landscape: admin's 11 columns need the width; customer sheet is portrait A4
            'format' => $isAdmin ? 'A4-L' : 'A4',
            'tempDir' => $tempDir,
            'margin_left' => $isAdmin ? 8 : 15,
            'margin_right' => $isAdmin ? 8 : 15,
            'margin_top' => $isAdmin ? 9 : 13,
            'margin_bottom' => $isAdmin ? 14 : 18,
            'default_font' => 'dejavusans',
            'default_font_size' => $isAdmin ? 8 : 9,
        ]);
        $pdf->SetTitle(($isAdmin ? 'Customer information ' : 'Invoice ') . $invoice->invoice_number);
        $pdf->SetHTMLFooter('<div style="text-align:center;font-size:7.5px;color:#94a3b8;">Cloud Technology · cloudtechnologybd.net · Page {PAGENO} of {nbpg}</div>');
        $pdf->WriteHTML($html);

        return $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function filename(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }
}
