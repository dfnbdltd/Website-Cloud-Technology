@extends('store.layout', ['pageTitle' => 'My Payments', 'navActive' => 'payments', 'topbarTitle' => 'My Store'])

@section('store')
    @include('store.partials.page-head', ['section' => $section, 'count' => $payments->count(), 'countLabel' => 'payment'])

    @if($payments->isEmpty())
        @include('store.partials.empty', ['section' => $section])
    @else
        <div class="store-totals">
            <div class="store-total">
                <small>RECEIVED</small>
                <strong>৳ {{ number_format($totals['received'], 2) }}</strong>
                <em>approved top-ups and paid orders</em>
            </div>
            <div class="store-total is-pending">
                <small>PENDING</small>
                <strong>৳ {{ number_format($totals['pending'], 2) }}</strong>
                <em>awaiting confirmation</em>
            </div>
            <div class="store-total is-count">
                <small>PAYMENTS</small>
                <strong>{{ $totals['count'] }}</strong>
                <em>all time</em>
            </div>
        </div>

        <div class="table-card store-table cols-payments">
            <div class="table-head">
                <span>REFERENCE</span>
                <span>WHAT IT PAID FOR</span>
                <span>METHOD</span>
                <span>DATE</span>
                <span>AMOUNT</span>
                <span>STATUS</span>
            </div>
            @foreach($payments as $payment)
                <div class="table-row">
                    <strong>{{ $payment['reference'] }}</strong>
                    <span>
                        {{ $payment['description'] }}
                        @if($payment['invoice'])
                            <div class="store-row-note">
                                <a class="store-link" href="{{ route('invoices.view', $payment['invoice']) }}">{{ $payment['invoice']->invoice_number }}</a>
                            </div>
                        @endif
                    </span>
                    <span>{{ ucfirst($payment['method'] ?: '—') }}</span>
                    <span>{{ $payment['at'] ? $payment['at']->format('d M Y') : '—' }}</span>
                    <b>৳ {{ number_format($payment['amount'], 2) }}</b>
                    <em class="table-status {{ $payment['status'] }}">{{ ucfirst($payment['status']) }}</em>
                </div>
            @endforeach
        </div>
    @endif
@endsection
