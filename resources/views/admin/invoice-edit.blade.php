<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Edit invoice ' . $invoice->invoice_number])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
<style>
    .inv-edit { max-width: 960px; }
    .inv-card { background:#fff; border:1px solid #e5ebf3; border-radius:10px; padding:20px; margin-top:16px; }
    .inv-card h3 { margin:0 0 4px; font-size:15px; color:#1f2937; }
    .inv-card .sub { margin:0 0 14px; font-size:12px; color:#8a97a5; }
    table.inv-lines { width:100%; border-collapse:collapse; font-size:12px; }
    table.inv-lines th { background:#f4f7fa; color:#91a1b0; font-size:9px; font-weight:800; letter-spacing:1px; text-transform:uppercase; text-align:left; padding:10px 8px; }
    table.inv-lines th:first-child { border-radius:6px 0 0 6px; }
    table.inv-lines th.num, table.inv-lines td.inv-num { text-align:right; }
    table.inv-lines td { border-bottom:1px solid #eef2f6; padding:8px 6px; vertical-align:middle; }
    table.inv-lines tr.inv-line:hover td { background:#fafbfd; }
    table.inv-lines td.inv-sl { font-weight:800; color:#2563eb; width:34px; text-align:center; }
    table.inv-lines input, table.inv-lines select { width:100%; box-sizing:border-box; border:1px solid #dfe6ee; border-radius:6px; padding:8px 9px; font-size:12px; color:#1f2937; background:#fff; }
    table.inv-lines input:focus, table.inv-lines select:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.12); }
    table.inv-lines td.inv-num { white-space:nowrap; font-weight:700; color:#1f2937; }
    .inv-remove { width:26px; height:26px; border-radius:6px; border:1px solid #f3d4d4; color:#c0392b; background:#fff; cursor:pointer; font-weight:800; line-height:1; }
    .inv-remove:hover { background:#fdf2f2; }
    .inv-actions { display:flex; gap:10px; align-items:center; margin-top:14px; }
    .inv-add { display:inline-block; padding:9px 15px; border-radius:6px; border:1px dashed #bcd0e8; color:#2563eb; background:#f6f9fd; cursor:pointer; font-size:12px; font-weight:700; }
    .inv-add:hover { background:#eef4fc; }
    .inv-save { margin-left:auto; display:inline-flex; gap:10px; }
    .inv-primary { display:inline-block; padding:10px 18px; border-radius:6px; border:none; background:#2563eb; color:#fff; font-weight:700; font-size:12px; cursor:pointer; }
    .inv-primary:hover { background:#1d4ed8; }
    .inv-ghost { display:inline-block; padding:10px 16px; border-radius:6px; border:1px solid #dfe6ee; color:#4b5563; text-decoration:none; font-size:12px; font-weight:700; background:#fff; }
    .inv-fields { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:14px; }
    .inv-field label { display:block; font-size:9px; font-weight:800; letter-spacing:1px; text-transform:uppercase; color:#91a1b0; margin-bottom:6px; }
    .inv-field input, .inv-field select { width:100%; box-sizing:border-box; border:1px solid #dfe6ee; border-radius:6px; padding:8px 9px; font-size:12px; color:#1f2937; background:#fff; }
    .inv-field input:focus, .inv-field select:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.12); }
    .inv-summary { margin-top:16px; border-top:2px solid #eef2f6; padding-top:14px; display:flex; justify-content:flex-end; }
    .inv-summary table { min-width:250px; font-size:12px; border-collapse:collapse; }
    .inv-summary td { padding:4px 0; color:#4b5563; }
    .inv-summary td:last-child { text-align:right; font-weight:700; color:#1f2937; }
    .inv-summary tr.balance td { border-top:2px solid #eef2f6; padding-top:8px; font-size:13px; color:#b45309; font-weight:800; }
    .inv-hint { margin-top:14px; font-size:11px; color:#8a97a5; line-height:1.6; }
    .inv-hint b { color:#4b5563; }
</style></head>
<body class="domain-app">@include('partials.admin-sidebar', ['active' => 'invoices'])
<main class="domain-main">
<header class="domain-topbar"><div><span class="crumb">Operations / Invoices /</span><strong>{{ $invoice->invoice_number }}</strong></div><span class="top-avatar">A</span></header>
<div class="domain-content standalone-content inv-edit">
<span class="domain-kicker">EDIT INVOICE</span>
<h1 class="workspace-title">{{ $invoice->invoice_number }}.</h1>
<p class="workspace-subtitle">@if($invoice->user)Customer {{ $invoice->user->name }} ({{ $invoice->user->customer_uid }}) · @endif Source: {{ str_replace('App\\Models\\', '', (string) $invoice->source_type) ?: 'Manual' }} — line totals are recalculated on save.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.invoices.update', $invoice) }}">@csrf
<div class="inv-card">
<h3>Line items</h3>
<p class="sub">Each row = one billable item. Line total = quantity × years × unit price (a row with Years = 0 bills only once — use it for one-off/manual charges).</p>
<table class="inv-lines">
<thead><tr><th style="width:34px">SL</th><th>Description</th><th style="width:170px">Type</th><th style="width:80px" class="num">Years</th><th style="width:70px" class="num">Qty</th><th style="width:120px" class="num">Unit price (৳)</th><th style="width:120px" class="num">Line total</th><th style="width:34px"></th></tr></thead>
<tbody id="lines">
    @foreach(old('items', $invoice->items->toArray()) as $i => $item)
    <tr class="inv-line">
        <td class="inv-sl">{{ $i + 1 }}</td>
        <td><input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" required maxlength="255" placeholder="e.g. example.com — domain registration"></td>
        <td><select name="items[{{ $i }}][item_type]">@foreach(['domain-registration' => 'Domain registration', 'domain-renewal' => 'Domain renewal', 'hosting' => 'Hosting', 'topup' => 'Wallet top-up', 'manual' => 'Manual / one-off'] as $value => $label)<option value="{{ $value }}" @if(old('item_type.'.$i, $item['item_type'] ?? '') === $value) selected @endif>{{ $label }}</option>@endforeach</select></td>
        <td><input type="number" class="num-inv years" name="items[{{ $i }}][years]" value="{{ $item['years'] ?? 0 }}" min="0" max="10"></td>
        <td><input type="number" class="num-inv qty" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" min="1" max="999"></td>
        <td><input type="number" class="num-inv price" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" min="0" step="0.01" required></td>
        <td class="inv-num">৳ <span class="rowTotal">{{ number_format(($item['quantity'] ?? 1) * max(1, (int)($item['years'] ?? 0)) * (float)($item['unit_price'] ?? 0), 2) }}</span></td>
        <td><button type="button" class="inv-remove" title="Remove line" onclick="removeLine(this)">×</button></td>
    </tr>
    @endforeach
</tbody>
</table>
<button type="button" class="inv-add" onclick="addLine()">＋ Add another line</button>
<div class="inv-summary">
<table>
<tr><td>Subtotal</td><td>৳ <span id="pSub">{{ number_format($invoice->subtotal, 2) }}</span></td></tr>
<tr><td>VAT ({{ $invoice->vat_percent }}%)</td><td>৳ <span id="pVat">{{ number_format($invoice->vat_amount, 2) }}</span></td></tr>
<tr><td>Total</td><td>৳ <span id="pTotal">{{ number_format($invoice->total, 2) }}</span></td></tr>
<tr><td>Amount paid</td><td>৳ <span id="paidAmount">{{ number_format($invoice->amount_paid, 2) }}</span></td></tr>
<tr class="balance"><td>Balance due (preview)</td><td>৳ <span id="pDue">{{ number_format($invoice->balanceDue(), 2) }}</span></td></tr>
</table>
</div>
</div>
<div class="inv-card">
<h3>Invoice status &amp; terms</h3>
<p class="sub">Switching a PAID invoice whose total just grew (e.g. 3 years → 4) reopens it as UNPAID — the already-paid amount stays on record and only the difference is owed.</p>
<div class="inv-fields">
<div class="inv-field"><label>Status</label><select name="status">@foreach(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'void' => 'Void (no longer editable)'] as $value => $label)<option value="{{ $value }}" @if(old('status', $invoice->status) === $value) selected @endif>{{ $label }}</option>@endforeach</select></div>
<div class="inv-field"><label>Payment method</label><select name="payment_method"><option value="">— not set —</option>@foreach(['wallet' => 'Wallet balance', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank transfer'] as $m => $label)<option value="{{ $m }}" @if(old('payment_method', $invoice->payment_method) === $m) selected @endif>{{ $label }}</option>@endforeach</select></div>
<div class="inv-field"><label>VAT %</label><input type="number" class="vat" name="vat_percent" value="{{ old('vat_percent', $invoice->vat_percent) }}" min="0" max="100" step="0.01"></div>
<div class="inv-field"><label>Due date</label><input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}"></div>
</div>
<div class="inv-field" style="margin-top:14px"><label>Notes (shown to the customer on the invoice)</label><textarea name="notes" rows="2" style="width:100%;box-sizing:border-box;border:1px solid #dfe6ee;border-radius:6px;padding:8px 9px;font-size:12px;color:#1f2937">{{ old('notes', $invoice->notes) }}</textarea></div>
<div class="inv-actions">
<div class="inv-save"><a class="inv-ghost" href="{{ route('admin.invoices.show', $invoice) }}">Cancel</a><button type="submit" class="inv-primary">Save invoice — recompute totals</button></div>
</div>
<p class="inv-hint">The customer always sees this invoice at the same link: <b>{{ $invoice->invoice_number }}</b> — SL numbers renumber automatically on save; voided invoices are locked.</p>
</div>
</form>
</div>
</main>
<template id="lineTemplate"><tr class="inv-line">
<td class="inv-sl"></td>
<td><input type="text" name="items[__N__][description]" required maxlength="255" placeholder="e.g. example.com — domain registration"></td>
<td><select name="items[__N__][item_type]"><option value="domain-registration">Domain registration</option><option value="domain-renewal">Domain renewal</option><option value="hosting">Hosting</option><option value="topup">Wallet top-up</option><option value="manual">Manual / one-off</option></select></td>
<td><input type="number" class="num-inv years" name="items[__N__][years]" value="1" min="0" max="10"></td>
<td><input type="number" class="num-inv qty" name="items[__N__][quantity]" value="1" min="1" max="999"></td>
<td><input type="number" class="num-inv price" name="items[__N__][unit_price]" min="0" step="0.01" required placeholder="0.00"></td>
<td class="inv-num">৳ <span class="rowTotal">0.00</span></td>
<td><button type="button" class="inv-remove" title="Remove line" onclick="removeLine(this)">×</button></td>
</tr></template>
<script>
function num(el) { return parseFloat(el.value) || 0; }
function lineTotal(row) { var q = num(row.querySelector('.qty'))||1; var y = num(row.querySelector('.years')); var p = num(row.querySelector('.price')); return q * (y > 0 ? y : 1) * p; }
function renumber() { document.querySelectorAll('#lines .inv-line').forEach(function (row, i) { row.querySelector('.inv-sl').textContent = i + 1; row.querySelectorAll('[name]').forEach(function (f) { f.name = f.name.replace(/items\[\d+\]/, 'items[' + i + ']'); }); }); }
function recompute() { var sub = 0; document.querySelectorAll('#lines .inv-line').forEach(function (row) { var t = lineTotal(row); sub += t; row.querySelector('.rowTotal').textContent = t.toFixed(2); }); var vatInput = document.querySelector('input.vat'); var vatP = vatInput ? num(vatInput) : 0; var vat = sub * vatP / 100; var total = sub + vat;
    var paidEl = document.getElementById('paidAmount'); var paid = paidEl ? num(paidEl) : 0;
    document.getElementById('pSub').textContent = sub.toFixed(2); document.getElementById('pVat').textContent = vat.toFixed(2); document.getElementById('pTotal').textContent = total.toFixed(2); document.getElementById('pDue').textContent = Math.max(0, total - paid).toFixed(2); }
function addLine() { var t = document.getElementById('lineTemplate'); document.getElementById('lines').appendChild(t.content.cloneNode(true)); renumber(); recompute(); }
function removeLine(btn) { btn.closest('.inv-line').remove(); renumber(); recompute(); }
document.addEventListener('input', function (e) { if (e.target.closest('#lines') || e.target.classList.contains('vat')) { recompute(); } });
renumber(); recompute();
</script>
</body></html>
