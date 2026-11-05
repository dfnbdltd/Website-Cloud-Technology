@extends('store.layout', ['pageTitle' => 'My Domains', 'navActive' => 'my-domains', 'topbarTitle' => 'My Store'])

@section('store')
    @include('store.partials.page-head', ['section' => $section, 'count' => $domains->count() + $transfers->count(), 'countLabel' => 'domain'])

    @foreach($transfers as $transfer)
        <article class="store-item">
            <header class="store-item-head">
                <span class="store-item-icon tone-{{ $section['tone'] }}" aria-hidden="true">{{ $section['icon'] }}</span>
                <div class="store-item-title">
                    <h2>{{ $transfer['domain'] }}</h2>
                    <p>Domain transfer · order <b>{{ $transfer['order']->reference }}</b></p>
                </div>
                <span class="table-status {{ $transfer['status'] }}">{{ $transfer['status_label'] }}</span>
            </header>
            <div class="store-facts">
                <div class="store-fact">
                    <small>TRANSFER</small>
                    <strong>{{ $transfer['status_label'] }}</strong>
                    <em>{{ $transfer['status_message'] }}</em>
                </div>
                <div class="store-fact">
                    <small>PAID</small>
                    <strong>৳ {{ number_format($transfer['price'], 2) }}</strong>
                    <em>{{ ucfirst(str_replace('_', ' ', (string) $transfer['order']->payment_method ?: 'n/a')) }}</em>
                </div>
            </div>
            <footer class="store-item-foot">
                <div class="store-item-links">
                    @if($transfer['invoice'])
                        <a href="{{ route('invoices.view', $transfer['invoice']) }}">Invoice {{ $transfer['invoice']->invoice_number }}</a>
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
                    <p>
                        .{{ $domain['tld'] }} · {{ $domain['years'] }} year{{ $domain['years'] > 1 ? 's' : '' }} registration
                        · order <b>{{ $domain['order']->reference }}</b>
                    </p>
                </div>
                <span class="table-status {{ $domain['order']->payment_status }}">{{ ucfirst(str_replace('_', ' ', $domain['order']->status)) }}</span>
            </header>

            <div class="store-facts">
                <div class="store-fact">
                    <small>REGISTERED</small>
                    <strong>{{ $domain['registered_at'] ? $domain['registered_at']->format('d M Y') : '—' }}</strong>
                    <em>order placed</em>
                </div>
                <div class="store-fact">
                    <small>EXPIRES</small>
                    <strong>{{ $domain['expires_at'] ? $domain['expires_at']->format('d M Y') : '—' }}</strong>
                    @if($domain['days_left'] !== null)
                        <em>{{ $domain['days_left'] >= 0 ? $domain['days_left'] . ' days left' : 'expired' }}</em>
                    @endif
                </div>
                <div class="store-fact">
                    <small>RENEWAL PRICE</small>
                    <strong>৳ {{ number_format($domain['renewal_price'], 2) }}<i>/year</i></strong>
                    <em>after this term</em>
                </div>
                <div class="store-fact">
                    <small>PAID</small>
                    <strong>৳ {{ number_format($domain['price'] * $domain['years'], 2) }}</strong>
                    <em>{{ ucfirst(str_replace('_', ' ', (string) $domain['order']->payment_method ?: 'n/a')) }} · order ৳ {{ number_format($domain['order']->total, 2) }}</em>
                </div>
            </div>

            <footer class="store-item-foot">
                <div class="store-item-links">
                    @if($domain['invoice'])
                        <a href="{{ route('invoices.view', $domain['invoice']) }}">Invoice {{ $domain['invoice']->invoice_number }}</a>
                        <a href="{{ route('invoices.pdf', $domain['invoice']) }}?download=1">Download PDF</a>
                    @else
                        <span class="store-muted">Invoice appears once the payment is confirmed</span>
                    @endif
                    <a href="{{ route('domains.confirmation', $domain['order']->reference) }}">Order details</a>
                </div>

                @if($domain['registration_status'] === 'registered' && $domain['order']->status === 'active' && !isset(config('services.test_domains')[$domain['domain']]))
                    <details class="store-domain-manage" @if(session('manage_domain_item') === $domain['item_id']) open @endif>
                        <summary>Manage</summary>
                        <form method="POST" action="{{ route('mystore.domains.nameservers', $domain['item_id']) }}">
                            @csrf
                            <label for="nameservers-{{ $domain['item_id'] }}">Nameservers</label>
                            <p>Enter all nameservers, one per line. This replaces the domain's current nameservers at ResellerClub.</p>
                            <textarea id="nameservers-{{ $domain['item_id'] }}" name="nameservers" rows="4" required placeholder="ns1.example.com&#10;ns2.example.com">{{ session('manage_domain_item') === $domain['item_id'] ? (is_array(old('nameservers')) ? implode("\n", array_filter(old('nameservers'), 'is_string')) : old('nameservers', '')) : '' }}</textarea>
                            <button type="submit">Update nameservers</button>
                        </form>
                    </details>
                @endif

                @if($domain['payments']->isNotEmpty())
                    <div class="store-mini-payments">
                        <small>PAYMENTS ON THIS ORDER</small>
                        @foreach($domain['payments'] as $payment)
                            <div class="store-mini-payment">
                                <span>{{ ucfirst((string) $payment->provider ?: 'wallet') }} · {{ $payment->reference }}</span>
                                <span>{{ $payment->created_at ? $payment->created_at->format('d M Y') : '—' }}</span>
                                <b>৳ {{ number_format($payment->amount, 2) }}</b>
                                <em class="table-status {{ $payment->status }}">{{ ucfirst((string) $payment->status) }}</em>
                            </div>
                        @endforeach
                    </div>
                @endif
            </footer>
        </article>
    @endforeach

    @if($domains->isEmpty() && $transfers->isEmpty())
        @include('store.partials.empty', ['section' => $section])
    @endif
@endsection
