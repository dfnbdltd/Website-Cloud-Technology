<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'My Store'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'mystore', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'My Store', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">MY STORE</span><h1 class="workspace-title">Everything of yours, in one place.</h1><p class="workspace-subtitle">Domains, hosting, invoices, payments and your available balance.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

<div class="store-balance-row" id="balance">
<div class="balance-card"><small>AVAILABLE BALANCE</small><strong>৳ {{ number_format(session('customer.balance', 0), 2) }}</strong><span>{{ $pendingTopup ? $pendingTopup . ' top-up awaiting approval' : 'Top up instantly with bKash' }}</span></div>
<div class="balance-card alt"><small>PAID TO DATE</small><strong>৳ {{ number_format($paidTotal, 2) }}</strong><span>{{ $domainCount }} domain order{{ $domainCount === 1 ? '' : 's' }} placed</span></div>
<a class="add-balance-toggle button-like" href="{{ route('wallet.index') }}#add-balance">＋ Add Balance</a>
</div>

<div class="store-grid">
<section class="store-panel" id="my-domains"><div class="panel-head"><div><span class="domain-kicker">MY DOMAIN</span><h2>Domains</h2></div><a href="{{ route('domains.index') }}">Register new →</a></div>
@forelse($orders as $order)
@foreach($order->items as $item)
<div class="order-row"><span class="order-icon">◎</span><div><strong>{{ $item->domain }}</strong><small>{{ $order->reference }} · {{ $order->created_at->format('d M Y') }}</small></div><b class="table-status {{ $order->payment_status }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</b></div>
@endforeach
@empty
<div class="table-empty">No domains yet. <a href="{{ route('domains.index') }}">Register your first domain →</a></div>
@endforelse
</section>

<section class="store-panel" id="hosting"><div class="panel-head"><div><span class="domain-kicker">MY HOSTING SERVICES</span><h2>Hosting</h2></div></div>
<div class="table-empty">Hosting services will appear here after your next purchase.<br><a href="{{ route('under.construction', 'hosting-services') }}">Explore hosting plans →</a></div>
</section>

<section class="store-panel"><div class="panel-head"><div><span class="domain-kicker">INVOICES</span><h2>Recent invoices</h2></div><a href="{{ route('invoices') }}">View all →</a></div>
@forelse($orders as $order)
<div class="order-row"><span class="order-icon">▣</span><div><strong>{{ $order->reference }}</strong><small>{{ $order->created_at->format('d M Y') }}</small></div><b>৳ {{ number_format($order->total) }}</b><b class="table-status {{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</b></div>
@empty
<div class="table-empty">No invoices yet.</div>
@endforelse
</section>

<section class="store-panel" id="payments"><div class="panel-head"><div><span class="domain-kicker">PAYMENTS</span><h2>Top-ups & payments</h2></div></div>
@forelse($payments as $payment)
<div class="order-row"><span class="order-icon {{ $payment->status === 'approved' ? 'ok' : '' }}">৳</span><div><strong>{{ $payment->reference }}</strong><small>{{ ucfirst($payment->method) }} · {{ $payment->created_at->format('d M Y') }}</small></div><b>৳ {{ number_format($payment->amount, 2) }}</b><b class="table-status {{ $payment->status }}">{{ ucfirst($payment->status) }}</b></div>
@empty
<div class="table-empty">No payments yet. <a href="{{ route('wallet.index') }}#add-balance">Add balance →</a></div>
@endforelse
</section>
</div>
</div>
</main>

@include('partials.chat')
</body></html>
