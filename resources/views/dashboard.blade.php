<!doctype html>
<html lang="en"><head>@include('partials.head', ['title' => 'Dashboard'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'dashboard', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main" id="mainArea">
@include('partials.topbar', ['title' => 'Dashboard', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<div class="domain-content">@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@php($hostingLifecycle = $hostingLifecycle ?? collect())
<div class="dashboard-welcome"><div><span class="domain-kicker">CLIENT AREA</span><h1 class="workspace-title">Good to see you, {{ explode(' ', trim(session('customer.name', 'there')))[0] }}.</h1><p class="workspace-subtitle">Here is your CloudTechnology overview, kept simple and easy to act on.</p></div></div>
<section class="dashboard-service-cards" aria-label="Purchased services">
<article class="dashboard-service-card">
    <div class="dashboard-service-card-head"><span class="dashboard-service-icon">◇</span><span class="domain-kicker">MY DOMAIN</span></div>
    @if($domainLifecycle->isNotEmpty())
        @php($domain = $domainLifecycle->first())
        <h2>{{ $domain['domain'] }}</h2>
        <p>{{ $domainLifecycle->count() }} purchased domain{{ $domainLifecycle->count() > 1 ? 's' : '' }}</p>
        <strong class="dashboard-service-time {{ $domain['expired'] ? 'is-expired' : '' }}">{{ $domain['expired'] ? 'Expired' : $domain['remainingDays'] . ' days left' }}</strong>
        <small>Expires {{ $domain['expiryDate']->format('d M Y') }}</small>
    @else
        <h2>No domain purchased</h2>
        <p>You have not purchased any domain yet.</p>
        <a class="dashboard-service-action" href="{{ route('domains.index') }}">Register a domain →</a>
    @endif
</article>
<article class="dashboard-service-card">
    <div class="dashboard-service-card-head"><span class="dashboard-service-icon is-hosting">▤</span><span class="domain-kicker">MY HOSTING</span></div>
    @if($hostingLifecycle->isNotEmpty())
        @php($hosting = $hostingLifecycle->first())
        <h2>{{ $hosting['name'] }}</h2>
        <p>{{ $hostingLifecycle->count() }} purchased hosting plan{{ $hostingLifecycle->count() > 1 ? 's' : '' }}</p>
        <strong class="dashboard-service-time {{ $hosting['expired'] ? 'is-expired' : '' }}">{{ $hosting['expired'] ? 'Expired' : $hosting['remainingDays'] . ' days left' }}</strong>
        <small>Expires {{ $hosting['expiryDate']->format('d M Y') }}</small>
    @else
        <h2>No hosting purchased</h2>
        <p>You have not purchased any hosting yet.</p>
        <a class="dashboard-service-action" href="{{ route('client.hosting', 'shared') }}">Explore hosting plans →</a>
    @endif
</article>
</section>
<section class="dashboard-domains">
<div class="dashboard-domains-head"><div><span class="domain-kicker">MY DOMAINS</span><h2>Your purchased domains</h2></div><a href="{{ route('domains.index') }}">Register new <b>→</b></a></div>
<div class="dashboard-domain-list">
@php($hasDomainItems = false)
@foreach($domainLifecycle as $domain)
        @php($hasDomainItems = true)
        <div class="dashboard-domain-row">
            <div class="dashboard-domain-name"><strong>{{ $domain['domain'] }}</strong><small>Order {{ $domain['order']->reference }} · {{ $domain['purchaseDate']->format('d M Y') }}</small><div class="domain-row-progress"><i class="{{ $domain['expired'] ? 'is-expired' : '' }}" style="width:{{ max(3, $domain['progress']) }}%"></i></div></div>
            <div class="dashboard-domain-detail"><span>{{ $domain['years'] }} year{{ $domain['years'] > 1 ? 's' : '' }}</span><small>Registration</small></div>
            <div class="dashboard-domain-detail"><strong>{{ $domain['expired'] ? 'Expired' : $domain['remainingDays'] . ' days left' }}</strong><small>Expires {{ $domain['expiryDate']->format('d M Y') }}</small></div>
            <div class="dashboard-domain-actions"><span class="table-status {{ $domain['expired'] ? 'failed' : $domain['order']->payment_status }}">{{ $domain['expired'] ? 'Expired' : ucfirst(str_replace('_', ' ', $domain['order']->payment_status)) }}</span>@if($invoiceMap->has($domain['order']->id))<a class="dashboard-invoice-link" href="{{ route('invoices.pdf', $invoiceMap->get($domain['order']->id)) }}?download=1" title="Download invoice">↓ <span>Invoice</span></a>@endif</div>
        </div>
@endforeach
@unless($hasDomainItems)
    <div class="dashboard-domain-empty">No purchased domains yet. <a href="{{ route('domains.index') }}">Search for a domain →</a></div>
@endunless
</div>
</section>
@if($domainLifecycle->isNotEmpty())
<section class="domain-lifecycle-panel">
<div class="lifecycle-panel-head"><div><span class="domain-kicker">DOMAIN LIFECYCLE</span><h2>Track your domain time</h2><p>See how much of each registration period has passed and when it expires.</p></div><div class="lifecycle-overview-stat"><strong>{{ $domainLifecycle->where('expired', false)->count() }}</strong><span>active domains</span></div></div>
<div class="lifecycle-chart" aria-label="Domain registration lifecycle">
@foreach($domainLifecycle as $domain)
<div class="lifecycle-chart-row {{ $domain['expired'] ? 'is-expired' : '' }}">
<div class="lifecycle-chart-label"><strong>{{ $domain['domain'] }}</strong><small>{{ $domain['purchaseDate']->format('d M Y') }} — {{ $domain['expiryDate']->format('d M Y') }}</small></div>
<div class="lifecycle-circle" style="--lifecycle-progress:{{ min(100, max(0, $domain['progress'])) }}%" aria-label="{{ $domain['expired'] ? 'Expired' : $domain['remainingDays'] . ' days remaining' }}">
<div class="lifecycle-circle-inner"><strong>{{ $domain['expired'] ? '—' : $domain['remainingDays'] }}</strong><small>{{ $domain['expired'] ? 'expired' : 'days left' }}</small></div>
</div>
<div class="lifecycle-chart-percent"><strong>{{ $domain['expired'] ? 'Expired' : $domain['progress'] . '%' }}</strong><small>{{ $domain['expired'] ? 'Renewal needed' : 'registration used' }}</small></div>
</div>
@endforeach
</div>
</section>
@endif
@if($hostingInvoices->isNotEmpty())
<section class="dashboard-domains dashboard-hosting">
<div class="dashboard-domains-head"><div><span class="domain-kicker">MY HOSTING</span><h2>Your purchased hosting plans</h2></div><a href="{{ route('client.hosting', 'shared') }}">View plans <b>→</b></a></div>
<div class="dashboard-domain-list">
@foreach($hostingInvoices as $invoice)
    @foreach($invoice->items as $item)
        @if(stripos($item->item_type . ' ' . $item->description, 'hosting') !== false)
        <div class="dashboard-domain-row">
            <div class="dashboard-domain-name"><strong>{{ $item->description }}</strong><small>Invoice {{ $invoice->invoice_number }} · {{ $invoice->created_at->format('d M Y') }}</small></div>
            <div class="dashboard-domain-detail"><span>{{ $item->years > 1 ? $item->years . ' years' : '1 year' }}</span><small>Billing period</small></div>
            <div class="dashboard-domain-detail"><strong>৳ {{ number_format($item->line_total, 2) }}</strong><small>Plan amount</small></div>
            <span class="table-status {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
        </div>
        @endif
    @endforeach
@endforeach
</div>
</section>
@endif
</div>
</main>
@include('partials.chat')
</body></html>