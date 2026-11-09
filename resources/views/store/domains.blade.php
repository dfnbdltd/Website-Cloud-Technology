@extends('store.layout', ['pageTitle' => 'My Domains', 'navActive' => 'my-domains', 'topbarTitle' => 'My Store'])

@section('store')
@include('store.partials.page-head', ['section' => $section, 'count' => $domains->count() + $transfers->count(),
'countLabel' => 'domain'])

@foreach($transfers as $transfer)
<article class="store-item">
    <header class="store-item-head">
        <span class="store-item-icon tone-{{ $section['tone'] }}" aria-hidden="true">{{ $section['icon'] }}</span>
        <div class="store-item-title">
            <h2>{{ $transfer['domain'] }}</h2>
        </div>
        <span class="table-status {{ $transfer['status'] }}">{{ $transfer['status_label'] }}</span>
    </header>
    <div class="store-facts store-facts--transfer store-facts--inline">
        <div class="store-fact">
            <small>TRANSFER</small>
            <strong>{{ $transfer['status_label'] }}</strong>
        </div>
        <div class="store-fact">
            <small>PAID</small>
            <strong>৳ {{ number_format($transfer['price'], 2) }}</strong>
        </div>
    </div>
    <footer class="store-item-foot">
        <div class="store-item-links">
            @if($transfer['invoice'])
            <a href="{{ route('invoices.view', $transfer['invoice']) }}">Invoice
                {{ $transfer['invoice']->invoice_number }}</a>
            <a href="{{ route('invoices.pdf', $transfer['invoice']) }}?download=1">Download PDF</a>
            @endif
            <a href="{{ route('domains.confirmation', $transfer['order']->reference) }}">Order details</a>
        </div>
    </footer>
</article>
@endforeach

@foreach($domains as $domain)
<article class="store-item">
    <header class="store-item-head">
        <span class="store-item-icon tone-{{ $section['tone'] }}" aria-hidden="true">{{ $section['icon'] }}</span>
        <div class="store-item-title">
            <h2>{{ $domain['domain'] }}</h2>
        </div>
        <div class="store-item-actions">
            <span
                class="table-status {{ $domain['order']->payment_status }}">{{ ucfirst(str_replace('_', ' ', $domain['order']->status)) }}</span>
            @if($domain['registration_status'] === 'registered' && $domain['order']->status === 'active' &&
            !isset(config('services.test_domains')[$domain['domain']]))
            <a class="store-domain-manage-link"
                href="{{ route('mystore.domains.details', $domain['item_id']) }}">Manage</a>
            @endif
        </div>
    </header>

    <div class="store-facts store-facts--inline">
        <div class="store-fact">
            <small>REGISTERED</small>
            @if($domain['registered_at'])
            <span class="store-fact-join">on</span>
            @endif
            <strong>{{ $domain['registered_at'] ? $domain['registered_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>EXPIRES</small>
            @if($domain['expires_at'])
            <span class="store-fact-join">on</span>
            @endif
            <strong>{{ $domain['expires_at'] ? $domain['expires_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>RENEWAL</small>
            <strong>৳ {{ number_format($domain['renewal_price'], 2) }}<i>/year</i></strong>
        </div>
    </div>

</article>
@endforeach

@if($domains->isEmpty() && $transfers->isEmpty())
@include('store.partials.empty', ['section' => $section])
@endif
@endsection