<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Customers'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}"></head><body class="domain-app">@include('partials.admin-sidebar', ['active' => 'customers'])
<main class="domain-main"><header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Customers</strong></div><form method="GET" action="{{ route('admin.customers') }}" class="admin-search"><input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, UID, phone"></form></header>
<div class="domain-content standalone-content">
<span class="domain-kicker">CUSTOMERS</span><h1 class="workspace-title">Customers &amp; top-ups.</h1><p class="workspace-subtitle">{{ $customers->total() }} customer{{ $customers->total() === 1 ? '' : 's' }}{{ $search !== '' ? ' matching “' . $search . '”' : '' }}.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
<div class="table-card admin-table"><div class="table-head"><span>CUSTOMER</span><span>CONTACT</span><span>BALANCE</span><span>STATUS</span><span>ACTION</span></div>
@forelse($customers as $customer)
<div class="admin-client">
<div class="table-row admin-client-row"><strong>{{ $customer->name }}<small class="admin-domain">{{ $customer->customer_uid }} · joined {{ $customer->created_at ? $customer->created_at->format('d M Y') : '—' }}</small></strong><span>{{ $customer->email }}<small class="admin-domain">{{ $customer->phone }}</small></span><span>৳ {{ number_format($customer->balance, 2) }}</span><b class="table-status {{ $customer->email_verified_at ? 'paid' : 'pending' }}">{{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}</b><a class="admin-edit-link" href="{{ route('admin.customers.edit', $customer) }}">Edit</a></div>
<dl class="client-registration-fields">
<div><dt>First name</dt><dd>{{ $customer->first_name ?: '—' }}</dd></div>
<div><dt>Last name</dt><dd>{{ $customer->last_name ?: '—' }}</dd></div>
<div><dt>Company</dt><dd>{{ $customer->company_name ?: '—' }}</dd></div>
<div><dt>Address</dt><dd>{{ $customer->address1 ?: '—' }}{{ $customer->address2 ? ', ' . $customer->address2 : '' }}</dd></div>
<div><dt>City / State</dt><dd>{{ $customer->city ?: '—' }}{{ $customer->state ? ', ' . $customer->state : '' }}</dd></div>
<div><dt>Postcode / Country</dt><dd>{{ $customer->postcode ?: '—' }}{{ $customer->country ? ', ' . $customer->country : '' }}</dd></div>
<div><dt>Currency</dt><dd>{{ $customer->currency ?: '—' }}</dd></div>
<div><dt>Found us</dt><dd>{{ $customer->found_us ?: '—' }}</dd></div>
<div><dt>Newsletter</dt><dd>{{ $customer->newsletter ? 'Subscribed' : 'Not subscribed' }}</dd></div>
</dl>
</div>
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
<style>.admin-search input{height:38px;width:280px;max-width:50vw;border:1px solid var(--line);border-radius:8px;padding:0 12px;font:14px 'DM Sans',sans-serif}.admin-table .table-head,.admin-table .admin-client-row{grid-template-columns:1.2fr 1.2fr .7fr .7fr auto}.admin-table .table-row small{display:block;font-weight:400;color:var(--muted);font-size:12px}.admin-client+.admin-client{border-top:1px solid #e5ebf3}.client-registration-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 18px;margin:0;padding:0 20px 18px 20px}.client-registration-fields div{min-width:0}.client-registration-fields dt{font-size:9px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:#91a1b0}.client-registration-fields dd{margin:3px 0 0;font-size:11px;color:#536579;overflow-wrap:anywhere}.admin-edit-link{justify-self:start;text-decoration:none;background:#eef4ff;color:#315a9d;border-radius:5px;padding:6px 11px;font-size:11px;font-weight:700}.admin-pages{margin:14px 0}.admin-pages svg{width:16px}.admin-pages nav>div:first-child{display:none}.topup-actions{display:inline-flex;gap:8px;margin-left:10px}.topup-actions button{border:1px solid #16a34a;background:#16a34a;color:#fff;border-radius:6px;padding:4px 12px;cursor:pointer;font:600 12px 'DM Sans',sans-serif}.topup-actions button.reject{background:#fff;color:#dc2626;border-color:#fca5a5}@media(max-width:760px){.admin-table .table-head{display:none}.admin-table .admin-client-row{grid-template-columns:1fr 1fr;gap:10px;padding:14px}.admin-table .admin-client-row>span:nth-child(3),.admin-table .admin-client-row>b{display:none}.client-registration-fields{grid-template-columns:repeat(2,minmax(0,1fr));padding:0 14px 15px}}</style>
</body></html>
