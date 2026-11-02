@extends('store.layout', ['pageTitle' => 'My Store', 'navActive' => 'mystore', 'topbarTitle' => 'My Store'])

@section('store')
    @php($totalRows = collect($items)->sum(function ($rows) { return $rows->count(); }))

    <span class="domain-kicker">MY STORE</span>
    <h1 class="workspace-title">Everything of yours, in one place.</h1>
    <p class="workspace-subtitle">Domains, hosting, invoices, payments and your available balance — all seven sections, always listed, filled in as you buy.</p>

    <div class="store-balance-row" id="balance">
        <div class="balance-card">
            <small>AVAILABLE BALANCE</small>
            <strong>৳ {{ number_format(session('customer.balance', 0), 2) }}</strong>
            <span>{{ $pendingTopup ? $pendingTopup . ' top-up awaiting approval' : 'Top up instantly with bKash' }}</span>
        </div>
        <div class="balance-card alt">
            <small>PAID TO DATE</small>
            <strong>৳ {{ number_format($paidTotal, 2) }}</strong>
            <span>{{ $domainCount }} domain order{{ $domainCount === 1 ? '' : 's' }} placed</span>
        </div>
        <a class="add-balance-toggle button-like" href="{{ route('wallet.index') }}#add-balance">＋ Add Balance</a>
    </div>

    <div class="store-section-head">
        <div>
            <h2>All Items</h2>
            <p>Seven sections in order. Each one opens its own page with the full details.</p>
        </div>
        <span class="store-section-chip">
            {{ $totalRows }} item{{ $totalRows === 1 ? '' : 's' }} · {{ $items['domains']->count() }} domain{{ $items['domains']->count() === 1 ? '' : 's' }}
        </span>
    </div>

    <div class="store-card-grid">
        @component('store.partials.card', [
            'serial' => 1,
            'section' => $sections['domains'],
            'count' => $items['domains']->count(),
            'summary' => $summaries['domains'],
        ])
            @foreach($items['domains']->take(3) as $domain)
                <div class="store-card-row">
                    <div class="store-card-row-main">
                        <strong>{{ $domain['domain'] }}</strong>
                        <small>
                            @if($domain['expires_at'])
                                Renews {{ $domain['expires_at']->format('d M Y') }}
                            @else
                                Registered in your account
                            @endif
                        </small>
                    </div>
                    <b class="store-card-row-value">৳ {{ number_format($domain['renewal_price'], 2) }}<i>/yr</i></b>
                </div>
            @endforeach
            @if($items['domains']->count() > 3)
                <span class="store-card-more">+{{ $items['domains']->count() - 3 }} more</span>
            @endif
        @endcomponent

        @component('store.partials.card', [
            'serial' => 2,
            'section' => $sections['hosting'],
            'count' => $items['hosting']->count(),
            'summary' => $summaries['hosting'],
        ])
            @include('store.partials.service-rows', ['items' => $items['hosting'], 'limit' => 3])
        @endcomponent

        @component('store.partials.card', [
            'serial' => 3,
            'section' => $sections['ssl'],
            'count' => $items['ssl']->count(),
            'summary' => $summaries['ssl'],
        ])
            @include('store.partials.service-rows', ['items' => $items['ssl'], 'limit' => 3])
        @endcomponent

        @component('store.partials.card', [
            'serial' => 4,
            'section' => $sections['email'],
            'count' => $items['email']->count(),
            'summary' => $summaries['email'],
        ])
            @include('store.partials.service-rows', ['items' => $items['email'], 'limit' => 3])
        @endcomponent

        @component('store.partials.card', [
            'serial' => 5,
            'section' => $sections['sms'],
            'count' => $items['sms']->count(),
            'summary' => $summaries['sms'],
        ])
            @include('store.partials.service-rows', ['items' => $items['sms'], 'limit' => 3])
        @endcomponent

        @component('store.partials.card', [
            'serial' => 6,
            'section' => $sections['invoices'],
            'count' => $items['invoices']->count(),
            'summary' => $summaries['invoices'],
        ])
            @foreach($items['invoices']->take(3) as $order)
                <div class="store-card-row">
                    <div class="store-card-row-main">
                        <strong>{{ $order->reference }}</strong>
                        <small>{{ $order->created_at->format('d M Y') }} · {{ $order->items->count() }} domain{{ $order->items->count() === 1 ? '' : 's' }}</small>
                    </div>
                    <b class="store-card-row-value">৳ {{ number_format($order->total, 2) }}</b>
                </div>
            @endforeach
            @if($items['invoices']->count() > 3)
                <span class="store-card-more">+{{ $items['invoices']->count() - 3 }} more</span>
            @endif
        @endcomponent

        @component('store.partials.card', [
            'serial' => 7,
            'section' => $sections['payments'],
            'count' => $items['payments']->count(),
            'summary' => $summaries['payments'],
        ])
            @foreach($items['payments']->take(3) as $payment)
                <div class="store-card-row">
                    <div class="store-card-row-main">
                        <strong>{{ $payment['reference'] }}</strong>
                        <small>{{ ucfirst($payment['method']) }} · {{ $payment['at'] ? $payment['at']->format('d M Y') : '—' }}</small>
                    </div>
                    <b class="store-card-row-value">৳ {{ number_format($payment['amount'], 2) }}</b>
                </div>
            @endforeach
            @if($items['payments']->count() > 3)
                <span class="store-card-more">+{{ $items['payments']->count() - 3 }} more</span>
            @endif
        @endcomponent
    </div>
@endsection
