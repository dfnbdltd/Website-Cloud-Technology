<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Customers'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"><link rel="stylesheet" href="{{ asset('css/workspace.css') }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}"></head><body class="domain-app">@include('partials.admin-sidebar', ['active' => 'customers'])
<main class="domain-main"><header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Customers</strong></div><form method="GET" action="{{ route('admin.customers') }}" class="admin-search"><input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, UID, phone"></form></header>
<div class="domain-content standalone-content">
<span class="domain-kicker">CUSTOMERS</span><h1 class="workspace-title">Customers &amp; top-ups.</h1><p class="workspace-subtitle">{{ $customers->total() }} customer{{ $customers->total() === 1 ? '' : 's' }}{{ $search !== '' ? ' matching “' . $search . '”' : '' }}.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
<div class="table-card admin-table"><div class="table-head"><span>CUSTOMER</span><span>CONTACT</span><span>BALANCE</span><span>STATUS</span></div>
@forelse($customers as $customer)
<div class="table-row"><strong>{{ $customer->name }}<small class="admin-domain">{{ $customer->customer_uid }} · joined {{ $customer->created_at ? $customer->created_at->format('d M Y') : '—' }}</small></strong><span>{{ $customer->email }}<small class="admin-domain">{{ $customer->phone }}</small></span><span>৳ {{ number_format($customer->balance, 2) }}</span><b class="table-status {{ $customer->email_verified_at ? 'paid' : 'pending' }}">{{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}</b></div>
@empty
<div class="table-empty">No customers found.</div>
@endforelse
</div>
<div class="admin-pages">{{ $customers->links() }}</div>

<section class="admin-tickets" id="topups"><span class="domain-kicker">WALLET</span><h2>Recent top-ups</h2>
@forelse($topups as $topup)
<div class="ticket-row"><strong>{{ $topup->reference }} · ৳ {{ number_format($topup->amount, 2) }} · {{ optional($topup->user)->name }}</strong><span>{{ $topup->method === 'bkash' && $topup->transaction_id ? 'bKash ' . $topup->transaction_id : ucfirst($topup->method) . ($topup->transaction_id ? ' · ' . $topup->transaction_id : '') }} · {{ ucfirst($topup->status) }} · {{ $topup->created_at->format('d M Y, h:i A') }}</span>@if($topup->status === 'pending' && $topup->method !== 'bkash')<span class="topup-actions"><form method="POST" action="{{ route('admin.topups.approve', $topup) }}">@csrf<button type="submit">Approve</button></form><form method="POST" action="{{ route('admin.topups.reject', $topup) }}">@csrf<button type="submit" class="reject">Reject</button></form></span>@endif</div>
@empty
<div class="table-empty">No top-ups yet.</div>
@endforelse
</section>
</div></main>
<style>.admin-search input{height:38px;width:280px;max-width:50vw;border:1px solid var(--line);border-radius:8px;padding:0 12px;font:14px 'DM Sans',sans-serif}.admin-table .table-row small{display:block;font-weight:400;color:var(--muted);font-size:12px}.admin-pages{margin:14px 0}.admin-pages svg{width:16px}.admin-pages nav>div:first-child{display:none}.topup-actions{display:inline-flex;gap:8px;margin-left:10px}.topup-actions button{border:1px solid #16a34a;background:#16a34a;color:#fff;border-radius:6px;padding:4px 12px;cursor:pointer;font:600 12px 'DM Sans',sans-serif}.topup-actions button.reject{background:#fff;color:#dc2626;border-color:#fca5a5}</style>
</body></html>
