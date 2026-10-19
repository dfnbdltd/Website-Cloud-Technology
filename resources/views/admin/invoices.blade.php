<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin invoices'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
    <style>
        .invoice-table-wrap { overflow-x:auto; background:#fff; border:1px solid #e5ebf3; border-radius:12px; box-shadow:0 8px 24px rgba(31,41,55,.04); }
        table.invoice-table { width:100%; min-width:1120px; border-collapse:collapse; font-size:12px; }
        .invoice-table th { padding:13px 12px; background:#f7f9fc; border-bottom:1px solid #e5ebf3; color:#77879a; font-size:9px; font-weight:800; letter-spacing:.8px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .invoice-table td { padding:14px 12px; border-bottom:1px solid #edf1f5; color:#344256; vertical-align:top; }
        .invoice-table tr:last-child td { border-bottom:0; }
        .invoice-table .num { text-align:right; white-space:nowrap; }
        .invoice-table .serial { color:#8996a5; font-weight:700; }
        .invoice-id { color:#1f2937; font-weight:800; text-decoration:none; white-space:nowrap; }
        .invoice-id:hover { color:#2563eb; }
        .invoice-client { min-width:150px; font-weight:700; color:#1f2937; }
        .invoice-client small, .invoice-domain small { display:block; margin-top:4px; color:#8190a1; font-size:10px; font-weight:400; }
        .invoice-domain { min-width:180px; }
        .invoice-domain-line { display:block; margin-bottom:6px; color:#26364a; font-weight:700; }
        .invoice-domain-line:last-child { margin-bottom:0; }
        .invoice-money-line { display:block; margin-bottom:6px; }
        .invoice-money-line:last-child { margin-bottom:0; }
        .invoice-profit { color:#12805c; font-weight:800; }
        .invoice-paymode { white-space:nowrap; }
        .invoice-state { display:inline-block; padding:5px 9px; border-radius:999px; font-size:10px; font-weight:800; }
        .invoice-state.paid { color:#166534; background:#dcfce7; }
        .invoice-state.unpaid { color:#92400e; background:#fef3c7; }
        .invoice-state.void { color:#b91c1c; background:#fee2e2; }
        .invoice-actions { display:flex; gap:6px; }
        .invoice-action { display:inline-block; padding:6px 9px; border:1px solid #dfe6ee; border-radius:6px; color:#334155; font-size:10px; font-weight:700; text-decoration:none; }
        .invoice-action:hover { border-color:#93b4e7; color:#1d4ed8; background:#f7faff; }
        .invoice-search { display:flex; gap:8px; margin:18px 0 12px; }
        .invoice-search input { width:min(360px, 70vw); padding:10px 12px; border:1px solid #dfe6ee; border-radius:7px; font-size:12px; }
        .invoice-search button { padding:10px 15px; border:0; border-radius:7px; background:#2563eb; color:#fff; font-size:12px; font-weight:700; cursor:pointer; }
        .invoice-table-empty { padding:30px; text-align:center; color:#8190a1; }
        .invoice-table-note { margin:10px 2px 0; color:#8190a1; font-size:11px; }
    </style>
</head>
<body class="domain-app">
    @include('partials.admin-sidebar', ['active' => 'invoices'])
    <main class="domain-main">
        <header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Invoices</strong></div><span class="top-avatar">A</span></header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">BILLING</span>
            <h1 class="workspace-title">Invoice control room.</h1>
            <p class="workspace-subtitle">Review domain, customer, ResellerClub purchase-price snapshot, selling price and margin for every invoice.</p>
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

            <form class="invoice-search" method="GET" action="{{ route('admin.invoices') }}">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search invoice, domain or client" aria-label="Search invoices">
                <button type="submit">Search</button>
            </form>

            <div class="invoice-table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Invoice / domain</th>
                            <th>Client</th>
                            <th class="num">Years</th>
                            <th class="num">API purchase / year</th>
                            <th class="num">Selling / year</th>
                            <th class="num">Profit</th>
                            <th>Pay mode</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php
                                $source = $invoice->source;
                                $clientName = $invoice->user ? $invoice->user->name : (isset($source->customer_name) ? $source->customer_name : '—');
                                $clientEmail = $invoice->user ? $invoice->user->email : (isset($source->customer_email) ? $source->customer_email : 'Customer record unavailable');
                                $clientPhone = $invoice->user ? $invoice->user->phone : (isset($source->customer_phone) ? $source->customer_phone : null);
                                $domainItems = $invoice->items->filter(function ($item) {
                                    return in_array($item->item_type, ['domain-registration', 'domain-renewal'], true);
                                });
                                $hasUnknownCost = $domainItems->contains(function ($item) {
                                    return $item->purchase_price === null;
                                });
                                $purchaseTotal = $domainItems->sum(function ($item) {
                                    return (float) $item->purchase_price * max(1, (int) $item->years) * (int) $item->quantity;
                                });
                                $sellingTotal = $domainItems->sum('line_total');
                            @endphp
                            <tr>
                                <td class="serial">{{ $invoices->firstItem() + $loop->index }}</td>
                                <td class="invoice-domain">
                                    <a class="invoice-id" href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                    @forelse($domainItems as $item)
                                        <span class="invoice-domain-line">{{ preg_replace('/^Domain (registration|renewal) — /', '', $item->description) }}</span>
                                    @empty
                                        <small>{{ ucfirst($invoice->type) }} invoice</small>
                                    @endforelse
                                </td>
                                <td class="invoice-client">
                                    {{ $clientName }}
                                    <small>{{ $clientEmail }}@if($clientPhone) · {{ $clientPhone }}@endif</small>
                                </td>
                                <td class="num">
                                    @forelse($domainItems as $item)
                                        <span class="invoice-money-line">{{ $item->years ?: '—' }} @if($item->quantity > 1)<small>× {{ $item->quantity }}</small>@endif</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="num">
                                    @forelse($domainItems as $item)
                                        <span class="invoice-money-line">@if($item->purchase_price !== null)৳ {{ number_format($item->purchase_price, 2) }}@else —@endif</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="num">
                                    @forelse($domainItems as $item)
                                        <span class="invoice-money-line">৳ {{ number_format($item->unit_price, 2) }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="num invoice-profit">
                                    @if($domainItems->isEmpty() || $hasUnknownCost)
                                        —
                                    @else
                                        ৳ {{ number_format($sellingTotal - $purchaseTotal, 2) }}
                                    @endif
                                </td>
                                <td class="invoice-paymode">{{ $invoice->payment_method ? ucfirst($invoice->payment_method) : '—' }}</td>
                                <td><span class="invoice-state {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                                <td>
                                    <div class="invoice-actions">
                                        <a class="invoice-action" href="{{ route('admin.invoices.show', $invoice) }}">View</a>
                                        @unless($invoice->status === 'void')
                                            <a class="invoice-action" href="{{ route('admin.invoices.edit', $invoice) }}">Edit</a>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="invoice-table-empty" colspan="10">No invoices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="invoice-table-note">Profit = domain selling subtotal − saved purchase-price snapshot (before VAT). Purchase price is stored at checkout; unknown cost is shown as —.</p>
            {{ $invoices->links() }}
        </div>
    </main>
</body>
</html>
