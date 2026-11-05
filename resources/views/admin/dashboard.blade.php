<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Admin console'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'orders'])
    <main class="domain-main">
        <header class="domain-topbar">
            <div><span class="crumb">Operations /</span><strong>Admin console</strong></div>
            <span class="top-avatar">A</span>
        </header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">OPERATIONS</span>
            <h1 class="workspace-title">Order control room.</h1>
            <p class="workspace-subtitle">Review payment state, domain registration and customer support.</p>
            <div class="exchange-rate-card">
                <div>
                    <span class="exchange-rate-label">LIVE EXCHANGE RATE</span>
                    <strong>1 USD = ৳{{ number_format($exchangeRate['rate'], 2) }}</strong>
                    <small>{{ $exchangeRate['source'] === 'live' ? 'Updated from live market data.' : 'Live provider unavailable; using configured fallback.' }}</small>
                </div>
                <span class="exchange-rate-badge">{{ $exchangeRate['source'] === 'live' ? 'LIVE' : 'FALLBACK' }}</span>
            </div>
            @if(session('success'))
                <div class="domain-alert success">{{ session('success') }}</div>
            @endif
            @if($errors->has('registration'))
                <div class="domain-alert">{{ $errors->first('registration') }}</div>
            @endif
            @if($errors->has('transfer'))
                <div class="domain-alert">{{ $errors->first('transfer') }}</div>
            @endif
            <div class="table-card">
                <div class="table-head"><span>REFERENCE</span><span>CUSTOMER</span><span>AMOUNT</span><span>STATUS</span></div>
                @foreach($orders as $order)
                    @php($transferItems = $order->items->where('order_type', 'transfer'))
                    @php($firstTransferItem = $transferItems->first())
                    <div class="table-row">
                        <strong>{{ $order->reference }}<small class="admin-domain">{{ optional($order->items->first())->domain }} · {{ $firstTransferItem ? 'Transfer' : 'Registration' }}</small>
                            @foreach($transferItems as $transferItem)
                                @php($transferStatus = \App\Support\DomainTransferStatus::forOrderItem($order->status, $order->payment_status, $transferItem->registration_status))
                                <small class="admin-domain">{{ $transferItem->domain }} transfer: {{ $transferStatus['label'] }} · {{ $transferStatus['message'] }}</small>
                            @endforeach
                        </strong>
                        <span>{{ $order->customer_email }}</span>
                        <span>৳ {{ number_format($order->total) }}</span>
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                            @csrf
                            <select name="status" onchange="this.form.submit()">
                                @php($firstTransferStatus = $firstTransferItem ? \App\Support\DomainTransferStatus::forOrderItem($order->status, $order->payment_status, $firstTransferItem->registration_status) : null)
                                <option value="{{ $order->status }}">{{ $firstTransferStatus ? $firstTransferStatus['label'] : ucfirst(str_replace('_', ' ', $order->status)) }}</option>
                                @if($firstTransferItem)
                                    <option value="transfer_processing">Transfer Initialized</option>
                                    <option value="transfer_pending">Pending</option>
                                    <option value="transfer_failed">Failed</option>
                                    <option value="active">Success</option>
                                @else
                                    <option value="paid">Paid</option>
                                    <option value="registration_pending">Registration pending</option>
                                    <option value="transfer_pending">Transfer pending</option>
                                    <option value="transfer_failed">Transfer failed</option>
                                    <option value="active">Active</option>
                                    <option value="failed">Failed</option>
                                    <option value="cancelled">Cancelled</option>
                                @endif
                            </select>
                        </form>
                        @if($order->payment_status === 'paid' && in_array($order->status, ['paid', 'registration_pending', 'registration_processing'], true))
                            <form method="POST" action="{{ route('admin.orders.retry-registration', $order) }}">
                                @csrf
                                <button type="submit">Retry registration</button>
                            </form>
                        @endif
                        @if($order->payment_status === 'paid' && $order->status === 'transfer_failed' && $transferItems->isNotEmpty())
                            <form method="POST" action="{{ route('admin.orders.retry-transfer', $order) }}">
                                @csrf
                                @foreach($transferItems->where('registration_status', 'transfer_failed')->whereNull('transfer_auth_code') as $failedTransfer)
                                    <label class="admin-domain" for="transfer-auth-{{ $failedTransfer->id }}">EPP code for {{ $failedTransfer->domain }}</label>
                                    <input id="transfer-auth-{{ $failedTransfer->id }}" type="password" name="auth_codes[{{ $failedTransfer->id }}]" autocomplete="new-password" required>
                                @endforeach
                                <small class="admin-domain">Retry only after confirming the registrar has no transfer already in progress.</small>
                                <button type="submit">Retry transfer</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
            <section id="tickets" class="admin-tickets">
                <span class="domain-kicker">SUPPORT</span>
                <h2>Recent tickets</h2>
                @forelse($tickets as $ticket)
                    <div class="ticket-row">
                        <strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong>
                        <span>{{ ucfirst($ticket->status) }} · {{ $ticket->created_at->format('d M Y') }}</span>
                    </div>
                @empty
                    <div class="table-empty">No support tickets.</div>
                @endforelse
            </section>
        </div>
    </main>
</body>
</html>
