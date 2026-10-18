<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Renders invoices as PDF with mPDF (pure PHP — no external binary).
 * Print layout comes from resources/views/invoices/pdf.blade.php.
 */
class InvoicePdf
{
    public function render(Invoice $invoice): ?string
    {
        $tempDir = storage_path('framework/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $html = view('invoices.pdf', [
            'invoice' => $invoice->loadMissing('items', 'user'),
        ])->render();

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);
        $pdf->SetTitle('Invoice ' . $invoice->invoice_number);
        $pdf->WriteHTML($html);

        return $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function filename(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }
}
