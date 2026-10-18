<!doctype html>
<html lang="en">
<head><meta charset="utf-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; color:#1f2937; font-size:12px; }
    .brand { color:#2563eb; font-size:20px; font-weight:bold; }
    .brand small { display:block; font-size:10px; color:#6b7280; font-weight:normal; margin-top:3px; }
    .head-table { width:100%; margin-bottom:14px; }
    .meta { text-align:right; font-size:11px; line-height:1.8; }
    .badge { padding:2px 9px; border-radius:8px; font-size:9px; font-weight:bold; }
    .badge.unpaid { background:#fef3c7; color:#92400e; }
    .badge.paid { background:#dcfce7; color:#166534; }
    .badge.void { background:#fee2e2; color:#b91c1c; }
    .parties { width:100%; font-size:11px; margin-bottom:16px; }
    .parties h4 { margin:0 0 4px; font-size:10px; text-transform:uppercase; color:#6b7280; }
    table.items { width:100%; border-collapse:collapse; font-size:11px; }
    table.items th { background:#f4f7fb; text-align:left; padding:8px 10px; border-bottom:1px solid #e5e7eb; font-size:10px; text-transform:uppercase; color:#4b5563; }
    table.items td { padding:8px 10px; border-bottom:1px solid #eef0f3; vertical-align:top; }
    .num { text-align:right; }
    table.totals { margin-top:12px; width:250px; font-size:11px; border-collapse:collapse; margin-left:auto; }
    table.totals td { padding:4px 0; }
    table.totals td:last-child { text-align:right; font-weight:bold; }
    table.totals tr.balance td { font-size:13px; color:#92400e; border-top:2px solid #e5e7eb; padding-top:7px; }
    .notes { margin-top:16px; font-size:10px; color:#4b5563; background:#f9fafb; border:1px solid #e5e7eb; padding:10px 12px; }
    .foot { margin-top:20px; font-size:9px; color:#6b7280; text-align:center; border-top:1px solid #eef0f3; padding-top:10px; }
</style></head>
<body>
    <table class="head-table">
        <tr>
            <td><div class="brand">CloudTech<small>Domain &amp; hosting services · cloudtechnologybd</small></div></td>
            <td class="meta">
                <b>Invoice {{ $invoice->invoice_number }}</b><br>
                Date: {{ $invoice->created_at->format('d M Y') }}
                @if($invoice->due_date && !$invoice->isPaid())<br>Due: {{ $invoice->due_date->format('d M Y') }}@endif
                @if($invoice->paid_at)<br>Paid: {{ $invoice->paid_at->format('d M Y') }}@endif<br>
                <span class="badge {{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <h4>Billed to</h4>
                @if($invoice->user)
                    {{ $invoice->user->name }}<br>
                    @if($invoice->user->customer_uid)Customer ID: {{ $invoice->user->customer_uid }}<br>@endif
                    {{ $invoice->user->email }}@if($invoice->user->phone)<br>{{ $invoice->user->phone }}@endif
                @else
                    Customer record unavailable
                @endif
            </td>
            <td style="text-align:right">
                <h4>Payment</h4>
                Method: {{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Not set' }}<br>
                Currency: {{ $invoice->currency }}<br>
                Source: {{ str_replace('App\\Models\\', '', (string) $invoice->source_type) ?: 'Manual' }}@if($invoice->source) · {{ $invoice->source->reference }}@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <tr><th style="width:34px">SL</th><th>Description</th><th class="num">Years</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Total</th></tr>
        @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->sl_no }}</td>
                <td>{{ $item->description }}</td>
                <td class="num">{{ $item->years ?: '—' }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">BDT {{ number_format($item->unit_price, 2) }}</td>
                <td class="num"><b>BDT {{ number_format($item->line_total, 2) }}</b></td>
            </tr>
        @endforeach
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td>BDT {{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>VAT ({{ $invoice->vat_percent }}%)</td><td>BDT {{ number_format($invoice->vat_amount, 2) }}</td></tr>
        <tr><td>Total</td><td>BDT {{ number_format($invoice->total, 2) }}</td></tr>
        <tr><td>Amount paid</td><td>BDT {{ number_format($invoice->amount_paid, 2) }}</td></tr>
        <tr class="balance"><td>Balance due</td><td>BDT {{ number_format($invoice->balanceDue(), 2) }}</td></tr>
    </table>

    @if($invoice->notes)<div class="notes"><b>Notes:</b><br>{{ $invoice->notes }}</div>@endif

    <div class="foot">
        Payments: {{ \App\Models\Setting::get('manual_payment_number', 'see our support page') }} ·
        Support: {{ \App\Models\Setting::get('support_email', 'support@cloudtechnologybd.net') }} — Phone: {{ \App\Models\Setting::get('support_phone', '+880 9613 003 001') }}
    </div>
</body>
</html>
