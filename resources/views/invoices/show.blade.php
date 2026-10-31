<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Invoice ' . $invoice->invoice_number])
<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
<style>
    .invoice-sheet { background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; padding:26px; max-width:860px; margin:0 auto; font-family:Arial,Helvetica,sans-serif; color:#1f2937; }
    .invoice-toolbar { display:flex; gap:10px; align-items:center; justify-content:space-between; max-width:860px; margin:0 auto 14px; flex-wrap:wrap; }
    .invoice-toolbar .btnrow { display:flex; gap:10px; flex-wrap:wrap; }
    .invoice-toolbar .status-form { margin:0; }
    .invoice-toolbar .status-form button { font-family:inherit; cursor:pointer; }
    .invoice-flash { max-width:860px; margin:0 auto 12px; padding:10px 14px; border-radius:8px; background:#ecfdf3; border:1px solid #a7e8c4; color:#166534; font:13px Arial,Helvetica,sans-serif; }
    .invoice-flash.error { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
    .invoice-btn { display:inline-block; padding:9px 16px; border-radius:6px; border:1px solid transparent; background:#2563eb; color:#ffffff; font-weight:bold; text-decoration:none; font-size:13px; cursor:pointer; line-height:1.2; }
    .invoice-btn.ghost { background:#ffffff; color:#1f2937; border:1px solid #e5e7eb; }
    .invoice-btn.danger { background:#ffffff; color:#b91c1c; border:1px solid #fca5a5; }
    .invoice-head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #2563eb; padding-bottom:14px; margin-bottom:16px; }
    .invoice-brand { font-size:22px; font-weight:bold; color:#2563eb; }
    .invoice-brand small { display:block; font-size:12px; color:#6b7280; font-weight:normal; margin-top:4px; }
    .invoice-meta { text-align:right; font-size:13px; line-height:1.7; }
    .invoice-meta b { font-size:16px; }
    .inv-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:bold; letter-spacing:.5px; }
    .inv-badge.unpaid { background:#fef3c7; color:#92400e; }
    .inv-badge.paid { background:#dcfce7; color:#166534; }
    .inv-badge.void { background:#fee2e2; color:#b91c1c; }
    .inv-parties { display:flex; gap:24px; justify-content:space-between; font-size:13px; margin-bottom:18px; }
    .inv-parties h4 { margin:0 0 6px; font-size:12px; text-transform:uppercase; color:#6b7280; letter-spacing:.5px; }
    table.inv-items { width:100%; border-collapse:collapse; font-size:13px; }
    table.inv-items th { background:#f4f7fb; text-align:left; padding:10px 12px; border-bottom:2px solid #e5e7eb; font-size:12px; text-transform:uppercase; color:#4b5563; letter-spacing:.5px; }
    table.inv-items td { padding:11px 12px; border-bottom:1px solid #eef0f3; }
    table.inv-items td.num, table.inv-items th.num { text-align:right; white-space:nowrap; }
    table.inv-items td small { color:#6b7280; }
    table.inv-totals { margin-top:14px; width:280px; font-size:13px; border-collapse:collapse; margin-left:auto; }
    table.inv-totals td { padding:6px 0; }
    table.inv-totals td:last-child { text-align:right; font-weight:bold; }
    table.inv-totals tr.balance td { font-size:15px; font-weight:bold; color:#92400e; border-top:2px solid #e5e7eb; padding-top:9px; }
    .inv-notes { margin-top:18px; font-size:12px; color:#4b5563; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; }
    .inv-foot { margin-top:22px; font-size:12px; color:#6b7280; text-align:center; border-top:1px solid #eef0f3; padding-top:12px; }

    /* ---- Print: exactly the invoice sheet, nothing else ---- */
    @media print {
        @page { margin: 12mm 12mm; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        html, body { background:#ffffff !important; }
        /* hide everything that isn't the sheet */
        .invoice-toolbar, .invoice-flash, .revechat-fab, .revechat-minimize { display:none !important; }
        /* strip the page shell */
        section.domain-content { max-width:none !important; margin:0 !important; padding:0 !important; }
        body.domain-app { background:#fff !important; font-family:Arial,Helvetica,sans-serif !important; }
        /* the sheet becomes the paper itself */
        .invoice-sheet { border:0 !important; border-radius:0 !important; box-shadow:none !important; padding:0 !important; max-width:none !important; }
        /* keep the design colors (blue rule, green badge, header band) on paper */
        .invoice-head { border-bottom:2px solid #2563eb !important; }
        .inv-badge.paid { background:#dcfce7 !important; color:#166534 !important; }
        .inv-badge.unpaid { background:#fef3c7 !important; color:#92400e !important; }
        table.inv-items th { background:#f4f7fb !important; border-bottom:2px solid #e5e7eb !important; }
        table.inv-totals tr.balance td { border-top:2px solid #e5e7eb !important; }
        table.inv-items tr { page-break-inside:avoid; }
        a { color:inherit !important; text-decoration:none !important; }
        .inv-notes { background:#f9fafb !important; border:1px solid #e5e7eb !important; }
    }
</style></head>
<body class="domain-app">
<section class="domain-content standalone-content">
    <div class="invoice-toolbar">
        <a class="invoice-btn ghost" href="{{ $backUrl ?? route('invoices') }}">&larr; Back</a>
        <div class="btnrow">
            @if(($isAdmin ?? false) && $invoice->status !== \App\Models\Invoice::STATUS_VOID)
                <form method="POST" action="{{ route('admin.invoices.status', $invoice) }}" class="status-form">
                    @csrf
                    @if($invoice->status === \App\Models\Invoice::STATUS_PAID)
                        <input type="hidden" name="status" value="unpaid">
                        <button type="submit" class="invoice-btn danger">Mark as unpaid</button>
                    @else
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="invoice-btn">Mark as paid</button>
                    @endif
                </form>
                <a class="invoice-btn danger" href="{{ route('admin.invoices.edit', $invoice) }}">Edit invoice (SL, years, prices)</a>
            @endif
            <a class="invoice-btn" href="{{ route(($isAdmin ?? false) ? 'admin.invoices.pdf' : 'invoices.pdf', $invoice) }}?download=1">{{ ($isAdmin ?? false) ? 'Download customer information PDF' : 'Download invoice PDF' }}</a>
        </div>
    </div>

    @if(session('success'))<div class="invoice-flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="invoice-flash error">{{ $errors->first() }}</div>@endif

    <div class="invoice-sheet">
        <div class="invoice-head">
            <div class="invoice-brand">Cloud Technology<small>Domain &amp; hosting services · cloudtechnologybd</small></div>
            <div class="invoice-meta">
                <b>Invoice {{ $invoice->invoice_number }}</b><br>
                Date: {{ $invoice->created_at->format('d M Y') }}<br>
                @if($invoice->due_date && !$invoice->isPaid())Due: {{ $invoice->due_date->format('d M Y') }}<br>@endif
                @if($invoice->paid_at)Paid: {{ $invoice->paid_at->format('d M Y') }}<br>@endif
                <span class="inv-badge {{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </div>

        <div class="inv-parties">
            <div>
                <h4>Billed to</h4>
                @if($invoice->user)
                    {{ $invoice->user->name }}<br>
                    @if($invoice->user->customer_uid)Customer ID: {{ $invoice->user->customer_uid }}<br>@endif
                    @if($invoice->user->company_name){{ $invoice->user->company_name }}<br>@endif
                    {{ $invoice->user->email }}@if($invoice->user->phone)<br>{{ $invoice->user->phone }}@endif
                @else
                    Customer record unavailable
                @endif
            </div>
            <div>
                <h4>Payment</h4>
                Method: {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Not set' }}<br>
                Currency: {{ $invoice->currency }}<br>
                Source: {{ str_replace('App\\Models\\', '', (string) $invoice->source_type) ?: 'Manual' }}
                @if($invoice->source) · ref {{ $invoice->source->reference }}@endif
            </div>
        </div>

        <table class="inv-items">
            <tr>
                <th style="width:46px">SL</th><th>Description</th><th class="num">Years</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Total</th>
            </tr>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->sl_no }}</td>
                    <td>{{ $item->description }}<br><small>{{ $item->item_type ? str_replace('-', ' ', $item->item_type) : 'manual item' }}</small></td>
                    <td class="num">{{ $item->years ?: '—' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">৳ {{ number_format($item->unit_price, 2) }}</td>
                    <td class="num"><b>৳ {{ number_format($item->line_total, 2) }}</b></td>
                </tr>
            @endforeach
        </table>

        <table class="inv-totals">
            <tr><td>Subtotal</td><td>৳ {{ number_format($invoice->subtotal, 2) }}</td></tr>
            <tr><td>VAT ({{ $invoice->vat_percent }}%)</td><td>৳ {{ number_format($invoice->vat_amount, 2) }}</td></tr>
            <tr><td>Total</td><td>৳ {{ number_format($invoice->total, 2) }}</td></tr>
            <tr><td>Amount paid</td><td style="color:#166534">৳ {{ number_format($invoice->amount_paid, 2) }}</td></tr>
            <tr class="balance"><td>Balance due</td><td>৳ {{ number_format($invoice->balanceDue(), 2) }}</td></tr>
        </table>

        @if($invoice->notes)<div class="inv-notes"><b>Notes:</b><br>{{ $invoice->notes }}</div>@endif
        <div class="inv-foot">
            Payments: {{ \App\Models\Setting::get('manual_payment_number', 'see our support page') }} ·
            Support: {{ \App\Models\Setting::get('support_email', 'support@cloudtechnologybd.net') }} — Phone: {{ \App\Models\Setting::get('support_phone', '+880 9613 003 001') }}
        </div>
    </div>
</section>
@include('partials.chat')
</body></html>
