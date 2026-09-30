<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Wallet'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'balance', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'Wallet', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">WALLET</span><h1 class="workspace-title">Available balance</h1><p class="workspace-subtitle">Top up instantly with bKash and use your balance for domains and renewals.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

<div class="wallet-grid">
<div class="wallet-balance" id="balance"><small>AVAILABLE BALANCE</small><strong>৳ {{ number_format($balance, 2) }}</strong><span>{{ $pendingTopup ? $pendingTopup . ' manual top-up awaiting approval' : 'Ready to use on invoices & renewals' }}</span></div>

<form class="wallet-topup" id="add-balance" method="POST" action="{{ route('wallet.bkash.start') }}">@csrf
<div class="wallet-topup-head"><span class="bk-dot">bK</span><div><h2>Add balance with bKash</h2><small>Paid instantly — credited to your wallet the moment bKash confirms.</small></div></div>
<label>Amount (BDT)<input type="number" name="amount" min="10" max="100000" step="1" value="{{ old('amount') }}" placeholder="e.g. 1000" required></label>
<div class="wallet-quick">@foreach([500, 1000, 2000, 5000] as $quick)<button type="button" onclick="this.form.amount.value={{ $quick }}">৳{{ number_format($quick) }}</button>@endforeach</div>
<button class="wallet-pay" type="submit" {{ $gatewayReady ? '' : 'disabled' }}>Continue to bKash →</button>
@unless($gatewayReady)<small class="wallet-warn">bKash is not configured on this server yet — use a manual payment below.</small>@endunless
</form>
</div>

<details class="store-panel wallet-manual">
<summary>Paid manually via Nagad, bKash personal or bank? Submit the details</summary>
<p>Send money to <b>{{ \App\Models\Setting::get('manual_payment_number') }}</b> (bKash / Nagad personal) or transfer to our bank, then submit the details below. Our billing team verifies and credits your balance.</p>
<form method="POST" action="{{ route('mystore.add-balance') }}">@csrf
<div class="store-fields">
<label>Amount (BDT) *<input type="number" name="amount" min="10" step="0.01" placeholder="e.g. 1000" required></label>
<label>Payment method *<select name="method" required><option value="nagad">Nagad</option><option value="bkash">bKash (personal)</option><option value="bank">Bank transfer</option></select></label>
<label>Sender number / Account<input type="text" name="sender_number" placeholder="01XXXXXXXXX"></label>
<label>Transaction ID<input type="text" name="transaction_id" placeholder="e.g. 9F7HK2LM"></label>
</div>
<button class="checkout-submit" type="submit">Submit for verification <b>→</b></button>
</form>
</details>

<section class="store-panel" id="payments"><div class="panel-head"><div><span class="domain-kicker">HISTORY</span><h2>Recent top-ups</h2></div></div>
@forelse($topups as $topup)
<div class="order-row"><span class="order-icon {{ $topup->status === 'approved' ? 'ok' : '' }}">৳</span><div><strong>{{ $topup->reference }}</strong><small>{{ $topup->method === 'bkash' && $topup->transaction_id ? 'bKash · ' . $topup->transaction_id : ucfirst($topup->method) }} · {{ $topup->created_at->format('d M Y, h:i A') }}</small></div><b>৳ {{ number_format($topup->amount, 2) }}</b><b class="table-status {{ $topup->status }}">{{ $topup->status === 'pending' && $topup->method === 'bkash' ? 'Not completed' : ucfirst($topup->status) }}</b></div>
@empty
<div class="table-empty">No top-ups yet.</div>
@endforelse
</section>
</div></main>
<script>if (window.location.hash === '#add-balance') { var f = document.querySelector('#add-balance input[name=amount]'); if (f) { f.focus(); } }</script>
@include('partials.chat')
</body></html>
