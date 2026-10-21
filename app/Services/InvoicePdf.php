<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Renders invoices as PDF with mPDF (pure PHP — no external binary).
 * Print layout comes from resources/views/invoices/pdf.blade.php.
 *
 * The same document adapts to its viewer: admins get the internal
 * customer-information sheet (purchase/profit columns), customers get the
 * same layout without any cost or margin data.
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
            'format' => 'A4-L', // landscape: 11 columns need the width
            'tempDir' => $tempDir,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 9,
            'margin_bottom' => 14,
            'default_font' => 'dejavusans',
            'default_font_size' => 8,
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
