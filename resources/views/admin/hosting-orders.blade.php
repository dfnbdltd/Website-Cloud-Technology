{{--
    Admin: paid hosting orders. The admin sets each one up on the server by hand,
    optionally adds a note (e.g. the cPanel username) and presses "Mark as hosted"
    (confirmation popup) - the customer is emailed and My Store shows Active.

    Data: $orders (paginator of HostingOrder, null when the table is missing),
          $invoices (Invoice keyed by hosting order id), $tableMissing,
          $newIds (ids that were unseen before this visit: they carry the NEW tag).
--}}
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin hosting orders'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
    <style>
        .ho-wrap { overflow-x:auto; background:#fff; border:1px solid #e5ebf3; border-radius:12px; box-shadow:0 8px 24px rgba(31,41,55,.04); }
        table.ho-table { width:100%; min-width:1400px; border-collapse:collapse; font-size:12px; }
        .ho-table th { padding:13px 12px; background:#f7f9fc; border-bottom:1px solid #e5ebf3; color:#77879a; font-size:9px; font-weight:800; letter-spacing:.8px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .ho-table td { padding:14px 12px; border-bottom:1px solid #edf1f5; color:#344256; vertical-align:middle; }
        .ho-table tr:last-child td { border-bottom:0; }
        .ho-serial { color:#8996a5; font-weight:700; }
        .ho-copy { display:flex; align-items:center; gap:8px; }
        .ho-copy strong { color:#1f2937; word-break:break-all; }
        .ho-copy-btn { flex:none; padding:5px 9px; border:1px solid #dfe6ee; border-radius:6px; background:#fff; color:#334155; font-size:10px; font-weight:700; cursor:pointer; }
        .ho-copy-btn:hover { border-color:#93b4e7; color:#1d4ed8; background:#f7faff; }
        .ho-copy-btn.is-done { border-color:#86d9b3; color:#166534; background:#effcf6; }
        .ho-plan strong { display:block; color:#1f2937; }
        .ho-plan small { display:block; margin-top:3px; color:#8190a1; font-size:10px; }
        .ho-invoice { color:#1f2937; font-weight:800; text-decoration:none; white-space:nowrap; }
        .ho-invoice:hover { color:#2563eb; }
        .ho-state { display:inline-block; padding:5px 9px; border-radius:999px; background:#fef3c7; color:#92400e; font-size:10px; font-weight:800; white-space:nowrap; }
        .ho-state.is-hosted { background:#dcfce7; color:#166534; }
        .ho-note-input { width:210px; min-height:54px; padding:8px 10px; border:1px solid #dfe6ee; border-radius:7px; font-size:12px; font-family:inherit; resize:vertical; }
        .ho-note-text { max-width:210px; color:#475569; white-space:pre-line; word-break:break-word; }
        .ho-note-text small { display:block; margin-top:4px; color:#8190a1; font-size:10px; }
        .ho-mark { padding:9px 13px; border:0; border-radius:7px; background:#16a34a; color:#fff; font-size:11px; font-weight:800; cursor:pointer; white-space:nowrap; }
        .ho-mark:hover { background:#15803d; }
        .ho-mark:disabled { background:#cbd5e1; color:#64748b; cursor:not-allowed; }
        .ho-modal { position:fixed; inset:0; z-index:50; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.5); }
        .ho-modal.is-open { display:flex; }
        .ho-dialog { width:100%; max-width:440px; padding:26px; border-radius:14px; background:#fff; box-shadow:0 24px 60px rgba(15,23,42,.3); }
        .ho-dialog h2 { margin:0 0 6px; font-size:18px; color:#1f2937; }
        .ho-dialog p { margin:0 0 14px; color:#64748b; font-size:13px; }
        .ho-dialog dl { margin:0 0 18px; padding:14px; border-radius:10px; background:#f7f9fc; font-size:13px; }
        .ho-dialog dt { color:#8190a1; font-size:10px; font-weight:800; letter-spacing:.8px; text-transform:uppercase; }
        .ho-dialog dd { margin:2px 0 10px; color:#1f2937; font-weight:700; word-break:break-all; }
        .ho-dialog dd:last-child { margin-bottom:0; }
        .ho-dialog-actions { display:flex; justify-content:flex-end; gap:10px; }
        .ho-dialog-actions button { padding:10px 18px; border-radius:8px; font-size:13px; font-weight:800; cursor:pointer; border:1px solid #dfe6ee; background:#fff; color:#334155; }
        .ho-dialog-actions .ho-yes { border:0; background:#16a34a; color:#fff; }
        .ho-dialog-actions button:disabled { opacity:.6; cursor:not-allowed; }
        .ho-empty { padding:34px; text-align:center; color:#8190a1; }
        .ho-new { display:inline-block; margin-left:6px; padding:2px 7px; border-radius:999px; background:#dc2626; color:#fff; font-size:9px; font-weight:800; letter-spacing:.6px; vertical-align:middle; }
        .ho-by strong { display:block; color:#1f2937; word-break:break-all; }
        .ho-by small { display:block; margin-top:3px; color:#8190a1; font-size:10px; }
        .ho-note { margin:10px 2px 0; color:#8190a1; font-size:11px; }
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'hosting-orders'])
    <main class="domain-main">
        <header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Hosting orders</strong></div><span class="top-avatar">A</span></header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">HOSTING</span>
            <h1 class="workspace-title">Hosting orders.</h1>
            <p class="workspace-subtitle">Paid orders only, waiting ones first. Set each hosting account up on the server with the domain and the customer's account email, then press Mark as hosted.</p>
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

            @if($tableMissing)
                <div class="domain-alert error">The hosting_orders table does not exist yet. Run database/hosting_orders.sql first.</div>
            @else
                <div class="ho-wrap">
                    <table class="ho-table">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Domain</th>
                                <th>Customer account email</th>
                                <th>Plan</th>
                                <th>Period</th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Note</th>
                                <th>Confirmed by</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $email = $order->owner && $order->owner->email ? $order->owner->email : $order->customer_email;
                                    $invoice = $invoices->get($order->id);
                                    $date = $order->paid_at ?: $order->created_at;
                                @endphp
                                <tr>
                                    <td class="ho-serial">{{ $orders->firstItem() + $loop->index }}</td>
                                    <td><div class="ho-copy"><strong>{{ $order->domain }}</strong>@if(in_array($order->id, $newIds ?? []))<span class="ho-new">NEW</span>@endif<button type="button" class="ho-copy-btn" data-copy="{{ $order->domain }}">Copy</button></div></td>
                                    <td><div class="ho-copy"><strong>{{ $email }}</strong><button type="button" class="ho-copy-btn" data-copy="{{ $email }}">Copy</button></div></td>
                                    <td class="ho-plan"><strong>{{ $order->plan_name }}</strong><small>{{ $order->planLabel() }}</small><small>{{ $order->product }}</small></td>
                                    <td>{{ $order->period_label }}</td>
                                    <td>
                                        @if($invoice)
                                            <a class="ho-invoice" href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $date ? $date->format('d M Y, H:i') : '—' }}</td>
                                    <td><span class="ho-state {{ $order->isHosted() ? 'is-hosted' : '' }}">{{ $order->statusLabel() }}</span></td>
                                    <td>
                                        @if($order->isHosted())
                                            <div class="ho-note-text">{{ $order->admin_note ?: '—' }}<small>Hosted {{ $order->hosted_at ? $order->hosted_at->format('d M Y, H:i') : '' }}</small></div>
                                        @else
                                            <textarea class="ho-note-input" form="hostForm{{ $order->id }}" name="note" maxlength="1000" placeholder="Optional note, e.g. cPanel username" aria-label="Optional note for {{ $order->domain }}"></textarea>
                                        @endif
                                    </td>
                                    <td class="ho-by">
                                        @if($order->isHosted() && $order->hosted_by_admin_email)
                                            <strong>{{ $order->hosted_by_admin_email }}</strong><small>{{ $order->hosted_at ? $order->hosted_at->format('d M Y, H:i') : '' }}</small>
                                        @elseif($order->isHosted())
                                            —
                                        @else
                                            <small>—</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->isHosted())
                                            <button type="button" class="ho-mark" disabled>Marked as hosted</button>
                                        @else
                                            <form id="hostForm{{ $order->id }}" method="POST" action="{{ route('admin.hosting.orders.hosted', $order->id) }}">
                                                @csrf
                                                <button type="button" class="ho-mark" data-form="hostForm{{ $order->id }}" data-domain="{{ $order->domain }}" data-email="{{ $email }}">Mark as hosted</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="ho-empty" colspan="11">No hosting orders are waiting for setup.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="ho-note">Waiting orders first (oldest first), hosted orders after. Every paid order also has its invoice in the Invoices panel.</p>
                {{ $orders->links() }}
            @endif
        </div>
    </main>

<div class="ho-modal" id="hoModal" role="dialog" aria-modal="true" aria-labelledby="hoModalTitle">
    <div class="ho-dialog">
        <h2 id="hoModalTitle">Mark as hosted?</h2>
        <p>Only continue once the hosting is set up on the server. The customer will be emailed.</p>
        <dl>
            <dt>Domain</dt><dd id="hoModalDomain"></dd>
            <dt>Customer email</dt><dd id="hoModalEmail"></dd>
        </dl>
        <div class="ho-dialog-actions">
            <button type="button" id="hoNo">No</button>
            <button type="button" class="ho-yes" id="hoYes">Yes, mark as hosted</button>
        </div>
    </div>
</div>
<script>
(function () {
    function fallbackCopy(text) {
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(area);
    }
    var modal = document.getElementById('hoModal');
    var pendingForm = null;
    var openButton = null;
    function closeModal() { modal.classList.remove('is-open'); pendingForm = null; openButton = null; }
    document.getElementById('hoNo').addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) { if (event.target === modal) { closeModal(); } });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeModal(); } });
    document.getElementById('hoYes').addEventListener('click', function () {
        if (!pendingForm) { return; }
        this.disabled = true;
        if (openButton) { openButton.disabled = true; openButton.textContent = 'Marking…'; }
        pendingForm.submit();
    });
    document.addEventListener('click', function (event) {
        var mark = event.target.closest('.ho-mark[data-form]');
        if (mark && !mark.disabled) {
            pendingForm = document.getElementById(mark.getAttribute('data-form'));
            openButton = mark;
            document.getElementById('hoModalDomain').textContent = mark.getAttribute('data-domain');
            document.getElementById('hoModalEmail').textContent = mark.getAttribute('data-email');
            document.getElementById('hoYes').disabled = false;
            modal.classList.add('is-open');
            return;
        }
        var button = event.target.closest('.ho-copy-btn');
        if (!button) { return; }
        var text = button.getAttribute('data-copy') || '';
        var done = function () {
            button.textContent = 'Copied';
            button.classList.add('is-done');
            setTimeout(function () { button.textContent = 'Copy'; button.classList.remove('is-done'); }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
        } else {
            fallbackCopy(text);
            done();
        }
    });
})();
</script>
</body>
</html>
