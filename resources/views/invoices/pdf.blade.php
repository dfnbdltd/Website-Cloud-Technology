<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans, sans-serif; color:#0f172a; font-size:{{ $isAdmin ? '8' : '9.5' }}pt; }
    @if($isAdmin)
    /* ===== Admin internal sheet (landscape report, unchanged) ===== */
    body { font-size:8pt; }
    .brand { font-size:9pt; letter-spacing:1.4px; font-weight:bold; color:#1e3a8a; }
    .brand small { display:block; font-size:7pt; letter-spacing:.2px; color:#64748b; font-weight:normal; margin-top:1px; }
    .doc-title { text-align:center; font-size:14pt; font-weight:bold; color:#0f172a; border-bottom:2px solid #1e3a8a; padding-bottom:5px; margin:9px 0 3px; }
    .doc-meta { text-align:center; font-size:7.5pt; color:#475569; }

    table.report { width:100%; border-collapse:collapse; table-layout:fixed; }
    table.report th { background:#e8eef6; border:0.5pt solid #a9bbd0; color:#1e293b; font-size:6.8pt; line-height:1.2; padding:4px 3px; text-align:left; vertical-align:middle; }
    table.report td { border:0.5pt solid #cbd5e1; padding:3.5px 4px; font-size:7pt; line-height:1.3; vertical-align:top; word-wrap:break-word; }
    table.report tr.alt td { background:#f8fafc; }
    table.report th.num { text-align:right; }
    table.report td.num { text-align:right; white-space:nowrap; font-size:6.8pt; }
    table.report .center { text-align:center; }
    table.report .email { font-size:6.2pt; }
    table.report tfoot td { background:#f1f5f9; border-top:1pt solid #64748b; font-size:7pt; font-weight:bold; padding:4px; }
    .link { color:#1d4ed8; text-decoration:none; }
    .muted { color:#94a3b8; font-weight:normal; }
    .profit { color:#0f766e; font-weight:bold; }

    table.totals-wrap { width:100%; margin-top:6px; }
    table.totals-wrap td { vertical-align:top; }
    table.totals { width:100%; border-collapse:collapse; }
    table.totals td { padding:2px 0; font-size:7.5pt; color:#334155; }
    table.totals td.amt { text-align:right; font-weight:bold; color:#0f172a; }
    table.totals tr.grand td { border-top:1pt solid #94a3b8; padding-top:4px; font-size:9pt; font-weight:bold; color:#0f172a; }
    table.totals tr.balance td { border-top:0.5pt solid #e2e8f0; padding-top:3px; font-size:9pt; font-weight:bold; color:#92400e; }

    .notes { margin-top:8px; border:0.5pt solid #cbd5e1; background:#f8fafc; padding:6px 8px; font-size:7pt; line-height:1.45; color:#334155; }
    .foot { margin-top:9px; border-top:0.5pt solid #cbd5e1; padding-top:5px; text-align:center; font-size:6.5pt; color:#64748b; }
    @else
    /* ===== Customer invoice sheet (portrait, mirrors the on-site design) ===== */
    .inv-head { width:100%; border-collapse:collapse; border-bottom:2px solid #2563eb; padding-bottom:10px; margin-bottom:18px; }
    .inv-head td { vertical-align:top; }
    .inv-head td.logo-cell { width:56px; padding-right:2px; }
    .inv-head td.logo-cell img { width:52px; height:47px; }
    .inv-head td.brand-cell { padding-left:8px; }
    .inv-brand { font-size:15.5pt; font-weight:bold; color:#2563eb; }
    .inv-tagline { font-size:7.5pt; color:#6b7280; margin-top:4px; }
    .inv-meta { text-align:right; font-size:9pt; line-height:1.62; color:#1f2937; }
    .inv-meta b { font-size:12.5pt; }
    table.inv-badge-wrap { margin-left:auto; margin-top:4px; border-collapse:separate; }
    table.inv-badge-wrap td { font-size:7.5pt; font-weight:bold; letter-spacing:.5px; padding:3px 12px; border-radius:9px; }
    table.inv-badge-wrap td.paid { background:#dcfce7; color:#166534; }
    table.inv-badge-wrap td.unpaid { background:#fef3c7; color:#92400e; }
    table.inv-badge-wrap td.void { background:#fee2e2; color:#b91c1c; }

    table.inv-parties { width:100%; border-collapse:collapse; margin-bottom:20px; }
    table.inv-parties td { width:50%; vertical-align:top; font-size:9.5pt; line-height:1.65; color:#1f2937; }
    table.inv-parties td.payment-cell { white-space:normal; }
    table.inv-parties .payment-line { white-space:nowrap; }
    table.inv-parties .payment-line .payment-method-value { font-weight:bold; }
    table.inv-parties h4 { margin:0 0 6px; font-size:7.5pt; text-transform:uppercase; color:#6b7280; letter-spacing:.5px; }

    table.inv-items { width:100%; border-collapse:collapse; }
    table.inv-items th { background:#f4f7fb; text-align:left; padding:7px 10px; border-bottom:1pt solid #e5e7eb; font-size:7.5pt; text-transform:uppercase; color:#4b5563; letter-spacing:.5px; }
    table.inv-items td { padding:9px 10px; border-bottom:0.5pt solid #eef0f3; font-size:9.5pt; vertical-align:top; }
    table.inv-items th:first-child, table.inv-items td:first-child { width:7%; white-space:nowrap; }
    table.inv-items th.sl-heading { white-space:nowrap; }
    table.inv-items th.num, table.inv-items td.num { text-align:right; white-space:nowrap; }
    table.inv-items td small { color:#6b7280; font-size:7.5pt; }

    table.inv-totals { width:320px; margin-left:auto; border-collapse:collapse; font-size:9.5pt; margin-top:14px; }
    table.inv-totals td { padding:5px 0; }
    table.inv-totals td:last-child { text-align:right; font-weight:bold; }
    table.inv-totals tr.paidrow td:last-child { color:#166534; }
    table.inv-totals tr.balance td { font-size:11pt; font-weight:bold; color:#92400e; border-top:1pt solid #e5e7eb; padding-top:9px; }

    .inv-notes { margin-top:16px; font-size:8.5pt; color:#4b5563; background:#f8fafc; border:0.5pt solid #e5e7eb; padding:9px 12px; }
    .inv-foot { margin-top:18px; border-top:0.5pt solid #eef0f3; padding-top:9px; text-align:center; font-size:7.5pt; color:#6b7280; }
    @endif
</style>
</head>
<body>
@if($isAdmin)
@php
    $user = $invoice->user;
    $source = $invoice->source;
    $provider = $source ? ($source->provider ?? null) : null;
    if (!$provider && $source && isset($source->method)) { $provider = $source->method; }
    $nonDomainRegister = $provider ? ucfirst($provider) : '—';

    $customerName = $user ? $user->name : ($source ? $source->customer_name : null);
    $customerName = $customerName ?: 'Customer';
    $customerEmail = $user && $user->email ? $user->email : ($source ? $source->customer_email : null);
    $customerPhone = $user && $user->phone ? $user->phone : ($source ? $source->customer_phone : null);
    $customerUid = $user && $user->customer_uid ? $user->customer_uid : '—';

    $addressParts = [];
    if ($user) {
        foreach (['address1', 'address2', 'city', 'state', 'postcode', 'country'] as $field) {
            $value = trim((string) $user->{$field});
            if ($value !== '') { $addressParts[] = $value; }
        }
    }
    $customerAddress = $addressParts ? implode(', ', $addressParts) : '—';

    $purchaseTotal = 0.0;
    $hasUnknownPurchase = false;
    foreach ($invoice->items as $line) {
        if ($line->purchase_price === null) {
            $hasUnknownPurchase = true;
            continue;
        }
        $purchaseTotal += (float) $line->purchase_price * max(1, (int) $line->years) * max(1, (int) $line->quantity);
    }
    $purchaseTotal = round($purchaseTotal, 2);
    $salesTotal = round((float) $invoice->items->sum('line_total'), 2);
    $profitTotal = round($salesTotal - $purchaseTotal, 2);
@endphp

<div class="brand">CLOUD TECHNOLOGY<small>cloudtechnologybd.net · premium hosting &amp; domains</small></div>

<div class="doc-title">{{ $isAdmin ? 'Cloud Technology Customer Information' : 'Cloud Technology — Invoice' }}</div>
<div class="doc-meta">
    Invoice {{ $invoice->invoice_number }} · {{ $invoice->created_at->format('d M Y') }} · {{ strtoupper($invoice->status) }}
    @if($invoice->payment_method) · {{ ucfirst($invoice->payment_method) }}@endif
    @if($invoice->due_date && !$invoice->isPaid()) · Due {{ $invoice->due_date->format('d M Y') }}@endif
    @if($invoice->paid_at) · Paid {{ $invoice->paid_at->format('d M Y') }}@endif
</div>
<table class="report">
    <thead>
        <tr>
            <th style="width:4%">Serial #</th>
            <th style="width:{{ $isAdmin ? '11%' : '14%' }}">Domain Name</th>
            <th style="width:{{ $isAdmin ? '8%' : '9%' }}">Domain Register Name</th>
            @if($isAdmin)
                <th style="width:9%" class="num">Product Purchase Amount BDT</th>
            @endif
            <th style="width:{{ $isAdmin ? '9%' : '10%' }}" class="num">Product Sales Amount</th>
            @if($isAdmin)
                <th style="width:9%" class="num">Profit Amount</th>
            @endif
            <th style="width:{{ $isAdmin ? '9%' : '11%' }}">Customer Name</th>
            <th style="width:{{ $isAdmin ? '7%' : '8%' }}">Customer ID</th>
            <th style="width:{{ $isAdmin ? '14%' : '20%' }}">Customer Address</th>
            <th style="width:{{ $isAdmin ? '12%' : '14%' }}">Customer Email/Login Name</th>
            <th style="width:{{ $isAdmin ? '8%' : '10%' }}">Phone Number</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
            @php
                $isDomain = in_array($item->item_type, ['domain-registration', 'domain-renewal', 'domain-transfer'], true);
                $domainName = $isDomain
                    ? preg_replace('/^Domain (registration|renewal|transfer)\s*[—-]\s*/u', '', $item->description)
                    : $item->description;
                $purchase = $item->purchase_price !== null
                    ? round((float) $item->purchase_price * max(1, (int) $item->years) * max(1, (int) $item->quantity), 2)
                    : null;
                $sales = round((float) $item->line_total, 2);
                $profit = $purchase !== null ? round($sales - $purchase, 2) : null;
                $registerName = $isDomain ? 'ResellerClub' : $nonDomainRegister;
            @endphp
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ $domainName }}</td>
                <td>{{ $registerName }}</td>
                @if($isAdmin)
                    <td class="num">@if($purchase !== null)BDT {{ number_format($purchase, 2) }}@else<span class="muted">—</span>@endif</td>
                @endif
                <td class="num">BDT {{ number_format($sales, 2) }}</td>
                @if($isAdmin)
                    <td class="num {{ $profit !== null ? 'profit' : '' }}">@if($profit !== null)BDT {{ number_format($profit, 2) }}@else<span class="muted">—</span>@endif</td>
                @endif
                <td>{{ $customerName }}</td>
                <td>{{ $customerUid }}</td>
                <td>{{ $customerAddress }}</td>
                <td class="email">
                    @if($customerEmail)<a class="link" href="mailto:{{ $customerEmail }}">{{ $customerEmail }}</a>@else<span class="muted">—</span>@endif
                </td>
                <td>{{ $customerPhone ?: '—' }}</td>
            </tr>
        @endforeach
    </tbody>
    @if($isAdmin)
        <tfoot>
            <tr>
                <td colspan="3">Totals</td>
                <td class="num">BDT {{ number_format($purchaseTotal, 2) }}@if($hasUnknownPurchase) <span class="muted">*</span>@endif</td>
                <td class="num">BDT {{ number_format($salesTotal, 2) }}</td>
                <td class="num">@if($hasUnknownPurchase)<span class="muted">—</span>@else BDT {{ number_format($profitTotal, 2) }}@endif</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    @endif
</table>

<table class="totals-wrap">
    <tr>
        <td style="width:58%"></td>
        <td style="width:42%">
            <table class="totals">
                <tr><td>Subtotal</td><td class="amt">BDT {{ number_format($invoice->subtotal, 2) }}</td></tr>
                <tr><td>VAT ({{ $invoice->vat_percent }}%)</td><td class="amt">BDT {{ number_format($invoice->vat_amount, 2) }}</td></tr>
                <tr class="grand"><td>Total</td><td class="amt">BDT {{ number_format($invoice->total, 2) }}</td></tr>
                <tr><td>Amount paid</td><td class="amt">BDT {{ number_format($invoice->amount_paid, 2) }}</td></tr>
                <tr class="balance"><td>Balance due</td><td class="amt">BDT {{ number_format($invoice->balanceDue(), 2) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

@if($isAdmin && $hasUnknownPurchase)
    <div style="margin-top:4px;font-size:6.8pt;color:#92400e;">* Some lines have no saved purchase price, so the purchase total excludes them and the profit total is shown as —.</div>
@endif

@if($invoice->notes)
    <div class="notes"><b>Notes:</b><br>{!! nl2br(e($invoice->notes)) !!}</div>
@endif

<div class="foot">
    Payments: {{ \App\Models\Setting::get('manual_payment_number', 'see our support page') }} ·
    Support: {{ \App\Models\Setting::get('support_email', 'support@cloudtechnologybd.net') }} — Phone: {{ \App\Models\Setting::get('support_phone', '+880 9613 003 001') }}
</div>
@else
@php
    $user = $invoice->user;
    $source = $invoice->source;
@endphp
<table class="inv-head" width="100%">
    <tr>
        <td class="logo-cell" width="58"><img src="{!! public_path('image/logo.png') !!}" width="52" height="47" alt="CloudTech"></td>
        <td class="brand-cell">
            <div class="inv-brand">Cloud Technology</div>
            <div class="inv-tagline">Domain &amp; hosting services · cloudtechnologybd</div>
        </td>
        <td class="inv-meta" width="42%">
            <b>Invoice {{ $invoice->invoice_number }}</b><br>
            Date: {{ $invoice->created_at->format('d M Y') }}<br>
            @if($invoice->due_date && !$invoice->isPaid())Due: {{ $invoice->due_date->format('d M Y') }}<br>@endif
            @if($invoice->paid_at)Paid: {{ $invoice->paid_at->format('d M Y') }}<br>@endif
            <table class="inv-badge-wrap"><tr><td class="{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</td></tr></table>
        </td>
    </tr>
</table>

<table class="inv-parties">
    <tr>
        <td>
            <h4>Billed to</h4>
            @if($user)
                {{ $user->name }}<br>
                @if($user->customer_uid)Customer ID: {{ $user->customer_uid }}<br>@endif
                @if($user->company_name){{ $user->company_name }}<br>@endif
                {{ $user->email }}@if($user->phone)<br>{{ $user->phone }}@endif
            @else
                Customer record unavailable
            @endif
        </td>
        <td class="payment-cell">
            <div class="payment-line">Payment Method: <b class="payment-method-value">{{ $invoice->payment_method ? ucfirst($invoice->payment_method) : 'Not set' }}</b></div>
            Currency: {{ $invoice->currency }}<br>
            Source: {{ str_replace('App\\Models\\', '', (string) $invoice->source_type) ?: 'Manual' }}
            @if($invoice->source) · ref {{ $invoice->source->reference }}@endif
        </td>
    </tr>
</table>

<table class="inv-items">
    <tr>
        <th class="sl-heading" style="width:7%">SL</th>
        <th>Description</th>
        <th class="num" style="width:11%">Years</th>
        <th class="num" style="width:9%">Qty</th>
        <th class="num" style="width:16%">Unit price</th>
        <th class="num" style="width:15%">Total</th>
    </tr>
    @foreach($invoice->items as $item)
        <tr>
            <td>{{ $item->sl_no }}</td>
            <td>{{ $item->description }}<br><small>{{ $item->item_type ? str_replace('-', ' ', $item->item_type) : 'manual item' }}</small></td>
            <td class="num">{{ $item->years ?: '—' }}</td>
            <td class="num">{{ $item->quantity }}</td>
            <td class="num">BDT {{ number_format($item->unit_price, 2) }}</td>
            <td class="num"><b>BDT {{ number_format($item->line_total, 2) }}</b></td>
        </tr>
    @endforeach
</table>

<table class="inv-totals">
    <tr><td>Subtotal</td><td>BDT {{ number_format($invoice->subtotal, 2) }}</td></tr>
    <tr><td>VAT ({{ $invoice->vat_percent }}%)</td><td>BDT {{ number_format($invoice->vat_amount, 2) }}</td></tr>
    <tr><td>Total</td><td>BDT {{ number_format($invoice->total, 2) }}</td></tr>
    <tr class="paidrow"><td>Amount paid</td><td>BDT {{ number_format($invoice->amount_paid, 2) }}</td></tr>
    <tr class="balance"><td>Balance due</td><td>BDT {{ number_format($invoice->balanceDue(), 2) }}</td></tr>
</table>

@if($invoice->notes)
    <div class="inv-notes"><b>Notes:</b><br>{!! nl2br(e($invoice->notes)) !!}</div>
@endif

<div class="inv-foot">
    Payments: {{ \App\Models\Setting::get('manual_payment_number', 'see our support page') }} ·
    Support: {{ \App\Models\Setting::get('support_email', 'support@cloudtechnologybd.net') }} — Phone: {{ \App\Models\Setting::get('support_phone', '+880 9613 003 001') }}
</div>
@endif
</body>
</html>
