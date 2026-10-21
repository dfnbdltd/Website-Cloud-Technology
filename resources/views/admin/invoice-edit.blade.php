<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Edit invoice ' . $invoice->invoice_number])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
<style>
    /* Full-width admin workbench */
    .inv-edit.domain-content { max-width:1680px; width:100%; margin:auto; padding:26px 32px 70px; }
    .inv-page-head { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; flex-wrap:wrap; }
    .inv-page-head .workspace-title { margin:6px 0 4px; }
    .inv-page-head .workspace-subtitle { margin:0; }
    .inv-head-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .inv-pill { font:800 10px 'Manrope',sans-serif; letter-spacing:1px; text-transform:uppercase; padding:7px 11px; border-radius:99px; border:1px solid #e5e7eb; }
    .inv-pill.paid { background:#ecfdf3; color:#067647; border-color:#abefc6; }
    .inv-pill.unpaid { background:#fffaeb; color:#b54708; border-color:#fedf89; }
    .inv-pill.void { background:#f3f4f6; color:#6b7280; }

    .inv-layout { display:grid; grid-template-columns:minmax(0,1fr) 368px; gap:16px; align-items:start; margin-top:16px; }
    .inv-side { display:grid; gap:14px; min-width:0; }

    .inv-card { background:#fff; border:1px solid #e5ebf3; border-radius:12px; padding:18px 20px; }
    .inv-card h3 { margin:0 0 3px; font-size:15px; color:#1f2937; display:flex; align-items:center; gap:8px; }
    .inv-card .sub { margin:0; font-size:12px; line-height:1.55; color:#8a97a5; }
    .inv-count { display:inline-grid; place-items:center; min-width:20px; height:20px; padding:0 6px; border-radius:99px; background:#eef5ff; color:#2563eb; font:800 11px 'Manrope',sans-serif; }

    /* ---- Line cards: compact 2-row grid, labels above each field ---- */
    .inv-lines-stack { display:grid; gap:12px; margin-top:14px; }
    .inv-line { border:1px solid #e5ebf3; border-radius:11px; background:#fbfdff; padding:12px 14px 2px; transition:border-color .15s ease,box-shadow .15s ease,background .15s ease; }
    .inv-line:focus-within { border-color:#bcd0e8; box-shadow:0 0 0 3px rgba(37,99,235,.06); background:#fff; }
    .inv-line-head { display:flex; align-items:center; gap:10px; padding-bottom:9px; margin-bottom:11px; border-bottom:1px solid #eef2f6; }
    .inv-line-title { font:800 10.5px 'Manrope',sans-serif; letter-spacing:1px; text-transform:uppercase; color:#91a1b0; }
    .inv-sl { color:#2563eb; }
    .inv-line-total { margin-left:auto; font-size:13px; font-weight:800; color:#1f2937; white-space:nowrap; }
    .inv-remove { width:26px; height:26px; flex:none; border-radius:7px; border:1px solid #f3d4d4; color:#c0392b; background:#fff; cursor:pointer; font-weight:800; font-size:15px; line-height:1; }
    .inv-remove:hover { background:#fdf2f2; }

    .inv-line-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:0 10px; }
    .inv-desc { grid-column:span 4; }
    .inv-type { grid-column:span 2; }
    .inv-years, .inv-qty { grid-column:span 1; }
    .inv-purchase, .inv-selling { grid-column:span 2; }

    .inv-field { display:block; margin:0 0 11px; min-width:0; }
    .inv-label, .inv-settings label { display:block; font-size:10px; font-weight:800; letter-spacing:.9px; text-transform:uppercase; color:#91a1b0; margin-bottom:5px; }
    .inv-line input[type=text], .inv-line input[type=number], .inv-line select,
    .inv-settings input, .inv-settings select, .inv-settings textarea {
        width:100%; box-sizing:border-box; border:1px solid #dfe6ee; border-radius:8px;
        padding:9px 11px; font:13.5px 'DM Sans',sans-serif; color:#1f2937; background:#fff;
        transition:border-color .15s ease,box-shadow .15s ease;
    }
    .inv-line input:focus, .inv-line select:focus,
    .inv-settings input:focus, .inv-settings select:focus, .inv-settings textarea:focus {
        outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12);
    }
    .inv-line input::placeholder, .inv-settings textarea::placeholder { color:#a9b8c8; }
    .inv-readonly { display:flex; align-items:center; min-height:38px; padding:0 11px; border:1px dashed #dfe6ee; border-radius:8px; background:#f4f7fa; font-size:13.5px; font-weight:700; color:#4b5563; }

    .inv-add { width:100%; margin-top:12px; padding:11px 16px; border-radius:9px; border:1px dashed #bcd0e8; color:#2563eb; background:#f6f9fd; cursor:pointer; font:700 13px 'DM Sans',sans-serif; }
    .inv-add:hover { background:#eef4fc; border-color:#9fc3ec; }

    /* ---- Sidebar: summary, settings, actions ---- */
    .inv-summary-card { border-top:3px solid #2563eb; }
    .inv-summary { display:grid; gap:2px; margin-top:8px; }
    .inv-srow { display:flex; align-items:baseline; justify-content:space-between; gap:10px; padding:6px 0; font-size:12.5px; color:#6b7280; }
    .inv-srow b { font-size:13px; color:#1f2937; font-weight:700; white-space:nowrap; }
    .inv-srow.total { border-top:1px solid #eef2f6; margin-top:3px; padding-top:10px; }
    .inv-srow.total b { font-size:16px; }
    .inv-due { margin-top:11px; background:#fffbeb; border:1px solid #fde68a; border-radius:9px; padding:11px 13px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .inv-due span { font-size:11px; font-weight:800; letter-spacing:.7px; text-transform:uppercase; color:#b45309; }
    .inv-due b { font-size:17px; color:#b45309; white-space:nowrap; }
    .inv-sum-note { margin:11px 0 0; font-size:11px; line-height:1.5; color:#8a97a5; }
    .inv-sum-note b { color:#4b5563; }

    .inv-settings { margin-top:12px; }
    .inv-settings .inv-field { margin-bottom:12px; }
    .inv-settings textarea { resize:vertical; min-height:64px; }
    .inv-settings select { cursor:pointer; }

    .inv-side-actions { display:flex; gap:10px; }
    .inv-side-actions .inv-primary { flex:1; text-align:center; }
    .inv-primary { display:inline-block; padding:11px 20px; border-radius:8px; border:none; background:#2563eb; color:#fff; font:700 13px 'DM Sans',sans-serif; cursor:pointer; }
    .inv-primary:hover { background:#1d4ed8; }
    .inv-ghost { display:inline-block; padding:11px 18px; border-radius:8px; border:1px solid #dfe6ee; color:#4b5563; text-decoration:none; font:700 13px 'DM Sans',sans-serif; background:#fff; cursor:pointer; text-align:center; }
    .inv-ghost:hover { background:#f8fafc; }
    .inv-hint { margin:12px 0 0; padding-top:11px; border-top:1px solid #eef2f6; font-size:11px; color:#8a97a5; line-height:1.6; }
    .inv-hint b { color:#4b5563; }

    @media(max-width:1240px) {
        .inv-layout { grid-template-columns:1fr; }
        .inv-side { grid-template-columns:1fr 1fr; align-items:start; }
        .inv-side-actions { grid-column:1 / -1; }
    }
    @media(max-width:760px) {
        .inv-edit.domain-content { padding:20px 16px 60px; }
        .inv-side { grid-template-columns:1fr; }
        .inv-side-actions { grid-column:auto; }
        .inv-line-grid { grid-template-columns:1fr 1fr; }
        .inv-desc, .inv-type { grid-column:span 2; }
        .inv-purchase, .inv-selling { grid-column:span 1; }
    }
    @media(max-width:460px) {
        .inv-line-grid { grid-template-columns:1fr; }
        .inv-desc, .inv-type, .inv-purchase, .inv-selling { grid-column:auto; }
    }
</style></head>
<body class="domain-app">@include('partials.admin-sidebar', ['active' => 'invoices'])
<main class="domain-main">
<header class="domain-topbar"><div><span class="crumb">Operations / Invoices /</span><strong>{{ $invoice->invoice_number }}</strong></div><span class="top-avatar">A</span></header>
<div class="domain-content standalone-content inv-edit">

    <div class="inv-page-head">
        <div>
            <span class="domain-kicker">EDIT INVOICE</span>
            <h1 class="workspace-title">{{ $invoice->invoice_number }}.</h1>
            <p class="workspace-subtitle">@if($invoice->user){{ $invoice->user->name }} ({{ $invoice->user->customer_uid }}) · @endif{{ str_replace('App\\Models\\', '', (string) $invoice->source_type) ?: 'Manual invoice' }} — totals are recalculated when you save.</p>
        </div>
        <div class="inv-head-actions">
            <span class="inv-pill {{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
            <a class="inv-ghost" href="{{ route('admin.invoices.show', $invoice) }}">Cancel</a>
            <button class="inv-primary" type="submit" form="invoiceEditForm">Save changes</button>
        </div>
    </div>

    @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

    <form id="invoiceEditForm" class="inv-edit-form" data-status="{{ $invoice->status }}" method="POST" action="{{ route('admin.invoices.update', $invoice) }}">@csrf
    @php $snapshots = $invoice->items->keyBy('id'); @endphp
    <div class="inv-layout">

        <section class="inv-card inv-lines-card">
            <h3>Line items <span class="inv-count">{{ $invoice->items->count() }}</span></h3>
            <p class="sub">Purchase price is the saved ResellerClub customer-price snapshot. Line total = quantity × years × selling price (Years = 0 bills once).</p>

            <div id="lines" class="inv-lines-stack">
                @foreach(old('items', $invoice->items->toArray()) as $i => $item)
                @php
                    $snapshot = !empty($item['id']) ? $snapshots->get((int) $item['id']) : null;
                    $purchase = $snapshot ? $snapshot->purchase_price : ($item['purchase_price'] ?? null);
                    $rowTotal = ($item['quantity'] ?? 1) * max(1, (int) ($item['years'] ?? 0)) * (float) ($item['unit_price'] ?? 0);
                @endphp
                <article class="inv-line">
                    <header class="inv-line-head">
                        <span class="inv-line-title">Line <span class="inv-sl">{{ $i + 1 }}</span></span>
                        <span class="inv-line-total">৳ <span class="rowTotal">{{ number_format($rowTotal, 2) }}</span></span>
                        <button type="button" class="inv-remove" title="Remove line" aria-label="Remove line" onclick="removeLine(this)">&times;</button>
                    </header>
                    <div class="inv-line-grid">
                        <label class="inv-field inv-desc">
                            <span class="inv-label">Description</span>
                            <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" required maxlength="255" placeholder="e.g. example.com — domain registration">
                        </label>
                        <label class="inv-field inv-type">
                            <span class="inv-label">Type</span>
                            <select name="items[{{ $i }}][item_type]">@foreach(['domain-registration' => 'Domain registration', 'domain-renewal' => 'Domain renewal', 'hosting' => 'Hosting', 'topup' => 'Wallet top-up', 'manual' => 'Manual / one-off'] as $value => $label)<option value="{{ $value }}" @if(old('items.'.$i.'.item_type', $item['item_type'] ?? '') === $value) selected @endif>{{ $label }}</option>@endforeach</select>
                        </label>
                        <label class="inv-field inv-years">
                            <span class="inv-label">Years</span>
                            <input type="number" class="years" name="items[{{ $i }}][years]" value="{{ $item['years'] ?? 0 }}" min="0" max="10">
                        </label>
                        <label class="inv-field inv-qty">
                            <span class="inv-label">Qty</span>
                            <input type="number" class="qty" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" min="1" max="999">
                        </label>
                        <div class="inv-field inv-purchase">
                            <span class="inv-label">Purchase / year</span>
                            <div class="inv-readonly">@if($purchase !== null)৳ {{ number_format((float) $purchase, 2) }}@else&mdash;@endif</div>
                        </div>
                        <label class="inv-field inv-selling">
                            <span class="inv-label">Selling / year (৳)</span>
                            <input type="number" class="price" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" min="0" step="0.01" required>
                        </label>
                    </div>
                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item['id'] ?? '' }}">
                </article>
                @endforeach
            </div>

            <button type="button" class="inv-add" onclick="addLine()">＋ Add another line</button>
        </section>

        <aside class="inv-side">
            <section class="inv-card inv-summary-card">
                <h3>Summary</h3>
                <div class="inv-summary">
                    <div class="inv-srow"><span>Subtotal</span><b>৳ <span id="pSub">{{ number_format($invoice->subtotal, 2) }}</span></b></div>
                    <div class="inv-srow"><span>VAT (<span class="vatLabel">{{ $invoice->vat_percent }}</span>%)</span><b>৳ <span id="pVat">{{ number_format($invoice->vat_amount, 2) }}</span></b></div>
                    <div class="inv-srow total"><span>Total</span><b>৳ <span id="pTotal">{{ number_format($invoice->total, 2) }}</span></b></div>
                    <div class="inv-srow"><span>Amount paid</span><b>৳ <span id="paidAmount" data-value="{{ $invoice->amount_paid }}">{{ number_format($invoice->amount_paid, 2) }}</span></b></div>
                </div>
                <div class="inv-due"><span>Balance due</span><b>৳ <span id="pDue">{{ number_format($invoice->balanceDue(), 2) }}</span></b></div>
                <p class="inv-sum-note">Live preview while you type. Customer link: <b>{{ $invoice->invoice_number }}</b></p>
            </section>

            <section class="inv-card inv-settings-card">
                <h3>Invoice status &amp; terms</h3>
                <div class="inv-settings">
                    <div class="inv-field">
                        <label for="invStatus">Status</label>
                        <select id="invStatus" name="status">@foreach(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'void' => 'Void (no longer editable)'] as $value => $label)<option value="{{ $value }}" @if(old('status', $invoice->status) === $value) selected @endif>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="inv-field">
                        <label for="invPayment">Payment method</label>
                        <select id="invPayment" name="payment_method"><option value="">— not set —</option>@foreach(['wallet' => 'Wallet balance', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'Bank transfer'] as $m => $label)<option value="{{ $m }}" @if(old('payment_method', $invoice->payment_method) === $m) selected @endif>{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="inv-field">
                        <label for="invVat">VAT %</label>
                        <input type="number" class="vat" id="invVat" name="vat_percent" value="{{ old('vat_percent', $invoice->vat_percent) }}" min="0" max="100" step="0.01">
                    </div>
                    <div class="inv-field">
                        <label for="invDue">Due date</label>
                        <input type="date" id="invDue" name="due_date" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="inv-field">
                        <label for="invNotes">Notes (shown to the customer)</label>
                        <textarea id="invNotes" name="notes" rows="3" placeholder="Optional note for the customer">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                </div>
                <p class="inv-hint">Choosing <b>Unpaid</b> on a paid invoice reverses the payment — the full total becomes due again. Extending a paid invoice (e.g. 3 → 4 years) keeps the received amount and reopens only the difference. Void invoices are locked.</p>
            </section>

            <div class="inv-side-actions">
                <a class="inv-ghost" href="{{ route('admin.invoices.show', $invoice) }}">Cancel</a>
                <button type="submit" class="inv-primary">Save invoice</button>
            </div>
        </aside>
    </div>
    </form>
</div>
</main>

<template id="lineTemplate"><article class="inv-line">
<header class="inv-line-head">
<span class="inv-line-title">Line <span class="inv-sl"></span></span>
<span class="inv-line-total">৳ <span class="rowTotal">0.00</span></span>
<button type="button" class="inv-remove" title="Remove line" aria-label="Remove line" onclick="removeLine(this)">&times;</button>
</header>
<div class="inv-line-grid">
<label class="inv-field inv-desc"><span class="inv-label">Description</span><input type="text" name="items[__N__][description]" required maxlength="255" placeholder="e.g. example.com — domain registration"></label>
<label class="inv-field inv-type"><span class="inv-label">Type</span><select name="items[__N__][item_type]"><option value="domain-registration">Domain registration</option><option value="domain-renewal">Domain renewal</option><option value="hosting">Hosting</option><option value="topup">Wallet top-up</option><option value="manual">Manual / one-off</option></select></label>
<label class="inv-field inv-years"><span class="inv-label">Years</span><input type="number" class="years" name="items[__N__][years]" value="1" min="0" max="10"></label>
<label class="inv-field inv-qty"><span class="inv-label">Qty</span><input type="number" class="qty" name="items[__N__][quantity]" value="1" min="1" max="999"></label>
<div class="inv-field inv-purchase"><span class="inv-label">Purchase / year</span><div class="inv-readonly">&mdash;</div></div>
<label class="inv-field inv-selling"><span class="inv-label">Selling / year (৳)</span><input type="number" class="price" name="items[__N__][unit_price]" min="0" step="0.01" required placeholder="0.00"></label>
</div>
</article></template>

<script>
function num(el) { return parseFloat(el.value) || 0; }
function lineTotal(row) { var q = num(row.querySelector('.qty')) || 1; var y = num(row.querySelector('.years')); var p = num(row.querySelector('.price')); return q * (y > 0 ? y : 1) * p; }
function renumber() { document.querySelectorAll('#lines .inv-line').forEach(function (row, i) { row.querySelector('.inv-sl').textContent = i + 1; row.querySelectorAll('[name]').forEach(function (f) { f.name = f.name.replace(/items\[\d+\]/, 'items[' + i + ']'); }); }); }
function recompute() {
    var sub = 0;
    document.querySelectorAll('#lines .inv-line').forEach(function (row) {
        var t = lineTotal(row); sub += t; row.querySelector('.rowTotal').textContent = t.toFixed(2);
    });
    var vatInput = document.querySelector('input.vat'); var vatP = vatInput ? num(vatInput) : 0;
    var vat = sub * vatP / 100; var total = sub + vat;
    var paidEl = document.getElementById('paidAmount');
    var paid = paidEl ? (parseFloat(paidEl.dataset.value || '0') || 0) : 0;
    var form = document.querySelector('.inv-edit-form');
    var statusSel = form ? form.querySelector('select[name="status"]') : null;
    var originalStatus = form ? form.dataset.status : '';
    if (statusSel) {
        // Mirror what saving will do: manual Unpaid reverses the payment, while
        // keeping a paid invoice paid applies the growth/reopen difference.
        if (statusSel.value === 'unpaid' && originalStatus === 'paid') { paid = 0; }
        if (statusSel.value === 'paid' && originalStatus !== 'paid') { paid = total; }
    }
    document.getElementById('pSub').textContent = sub.toFixed(2);
    document.getElementById('pVat').textContent = vat.toFixed(2);
    document.getElementById('pTotal').textContent = total.toFixed(2);
    document.getElementById('pDue').textContent = Math.max(0, total - paid).toFixed(2);
    var vatLabel = document.querySelector('.vatLabel'); if (vatLabel) { vatLabel.textContent = vatP; }
}
function addLine() { var t = document.getElementById('lineTemplate'); document.getElementById('lines').appendChild(t.content.cloneNode(true)); renumber(); recompute(); }
function removeLine(btn) { btn.closest('.inv-line').remove(); renumber(); recompute(); }
document.addEventListener('input', function (e) { if (e.target.closest('#lines') || e.target.classList.contains('vat')) { recompute(); } });
document.addEventListener('change', function (e) {
    if (e.target.matches('select[name="status"]')) {
        var pill = document.querySelector('.inv-pill');
        if (pill) { pill.className = 'inv-pill ' + e.target.value; pill.textContent = e.target.value.toUpperCase(); }
        recompute();
    }
});
(function () {
    var form = document.querySelector('.inv-edit-form');
    var status = form ? form.querySelector('select[name="status"]') : null;
    if (form && status) {
        form.addEventListener('submit', function (event) {
            if (status.value === 'void' && !window.confirm('Void this invoice? A voided invoice is locked and can no longer be edited.')) {
                event.preventDefault();
            }
        });
    }
})();
renumber(); recompute();
</script>
</body></html>
