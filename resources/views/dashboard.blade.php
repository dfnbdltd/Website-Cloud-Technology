<!doctype html>
<html lang="en"><head>@include('partials.head', ['title' => 'Dashboard'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'dashboard', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main" id="mainArea">
@include('partials.topbar', ['title' => 'Dashboard', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<div class="domain-content">@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
<div class="domain-hero"><div><span class="domain-kicker">CLIENT AREA</span><h1>Good to see you,<br><em>{{ session('customer.name', 'Client') }}.</em></h1><p>Manage your domains, invoices and support requests from one workspace.</p></div><div class="domain-orbit"><span>.com</span><span>.net</span><span>.bd</span><strong>◎</strong></div></div>
<section class="stats-grid-domain">
<div class="stat-tile"><span class="stat-tile-icon blue">◎</span><small>DOMAINS</small><strong>{{ $domainCount }}</strong><span class="stat-tile-foot">{{ $domainCount ? 'Registered with us' : 'No domains yet' }}</span></div>
<div class="stat-tile"><span class="stat-tile-icon green">✓</span><small>ORDERS</small><strong>{{ $orderCount }}</strong><span class="stat-tile-foot">{{ $pendingCount ? $pendingCount . ' awaiting payment' : 'All completed' }}</span></div>
<div class="stat-tile"><span class="stat-tile-icon orange">৳</span><small>OUTSTANDING</small><strong>৳ {{ number_format($outstanding) }}</strong><span class="stat-tile-foot">{{ $outstanding ? 'Payment due' : 'No payment due' }}</span></div>
<div class="stat-tile"><span class="stat-tile-icon purple">◔</span><small>OPEN TICKETS</small><strong>{{ $openTickets }}</strong><span class="stat-tile-foot">{{ $openTickets ? 'We are on it' : 'Nothing pending' }}</span></div>
</section>
<section class="dash-two-col">
<div class="panel-card">
<div class="panel-head"><div><span class="domain-kicker">RECENT ORDERS</span><h2>Your domain orders</h2></div><a href="{{ route('invoices') }}">View all →</a></div>
@forelse($orders as $order)
<div class="order-row"><span class="order-icon">◎</span><div><strong>{{ $order->reference }}</strong><small>{{ $order->created_at->format('d M Y') }}</small></div><b>৳ {{ number_format($order->total) }}</b><b class="table-status {{ $order->payment_status }}">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</b></div>
@empty
<div class="table-empty">No orders yet. <a href="{{ route('domains.index') }}">Register your first domain →</a></div>
@endforelse
<a class="checkout-button panel-cta" href="{{ route('domains.index') }}">Register a new domain <b>→</b></a>
</div>
<div class="panel-card">
<div class="panel-head"><div><span class="domain-kicker">QUICK ACTIONS</span><h2>Get things done</h2></div></div>
<div class="quick-actions"><a href="{{ route('mystore') }}"><b>🏪</b> My Store</a><a href="{{ route('domains.index') }}"><b>◎</b> Register domain</a><a href="{{ route('invoices') }}"><b>▣</b> View invoices</a><a href="{{ route('support') }}"><b>◔</b> Open ticket</a><a href="{{ route('profile.settings') }}"><b>⚙</b> Profile settings</a></div>
<div class="cart-trust"><span>✓</span><p>Secure client area<br><small>Your information is encrypted</small></p></div>
</div>
</section>
</div>
</main>
@include('partials.chat')
</body></html>