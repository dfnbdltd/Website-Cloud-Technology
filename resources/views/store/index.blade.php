<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'My Store'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'mystore', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'My Store', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">MY STORE</span><h1 class="workspace-title">Everything of yours, in one place.</h1><p class="workspace-subtitle">Domains, hosting, invoices, payments and your available balance.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

<div class="store-balance-row" id="balance">
<div class="balance-card"><small>AVAILABLE BALANCE</small><strong>৳ {{ number_format(session('customer.balance', 0), 2) }}</strong><span>{{ $pendingTopup ? $pendingTopup . ' top-up awaiting approval' : 'Instant use on invoices & renewals' }}</span></div>
<div class="balance-card alt"><small>PAID TO DATE</small><strong>৳ {{ number_format($paidTotal, 2) }}</strong><span>{{ $domainCount }} domain order{{ $domainCount === 1 ? '' : 's' }} placed</span></div>
<button class="add-balance-toggle button-like" type="button" onclick="toggleAddBalance()">＋ Add Balance</button>
</div>

<div class="store-panel add-balance-box" id="add-balance">
<h2>Add Balance</h2>
<p>Send money to <b>01713-401914</b> (bKash / Nagad personal) or transfer to our bank, then submit the details below. Our billing team verifies and credits your balance.</p>
<form method="POST" action="{{ route('mystore.add-balance') }}">@csrf
<div class="store-fields">
<label>Amount (BDT) *<input type="number" name="amount" min="10" step="0.01" placeholder="e.g. 1000" required></label>
<label>Payment method *<select name="method" required><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank transfer</option></select></label>
<label>Sender number / Account<input type="text" name="sender_number" placeholder="01XXXXXXXXX"></label>
<label>Transaction ID<input type="text" name="transaction_id" placeholder="e.g. 9F7HK2LM"></label>
</div>
<button class="checkout-submit" type="submit">Submit request <b>→</b></button>
</form>
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
<div class="table-empty">No payments yet. Use “Add Balance” above.</div>
@endforelse
</section>
</div>
</div>
</main>
<script>
function toggleAddBalance(){
  var box = document.getElementById('add-balance');
  if (!box) return;
  box.classList.toggle('open');
  if (box.classList.contains('open')) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
// Sidebar "Add Balance" link lands here with #add-balance — open the form.
if (window.location.hash === '#add-balance') {
  var box = document.getElementById('add-balance');
  if (box) { box.classList.add('open'); }
}
</script>
@include('partials.chat')
</body></html>
