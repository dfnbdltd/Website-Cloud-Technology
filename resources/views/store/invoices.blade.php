@extends('store.layout', ['pageTitle' => 'My Invoices', 'navActive' => 'invoices', 'topbarTitle' => 'My Store'])

@section('store')
    @include('store.partials.page-head', ['section' => $section, 'count' => $orders->count(), 'countLabel' => 'invoice'])

    @if($orders->isEmpty())
        @include('store.partials.empty', ['section' => $section])
    @else
        <div class="table-card store-table cols-invoices">
            <div class="table-head">
                <span>REFERENCE</span>
                <span>DATE</span>
                <span>AMOUNT</span>
                <span>STATUS</span>
                <span>INVOICE</span>
            </div>
            @foreach($orders as $order)
                @php($invoice = $invoiceMap[$order->id] ?? null)
                <div class="table-row">
                    <strong>{{ $order->reference }}</strong>
                    <span>{{ $order->created_at->format('d M Y') }}</span>
                    <span>
                        ৳ {{ number_format($order->total) }}
                        @if($invoice && $invoice->isPaid())
                            <div class="store-row-note">fully paid</div>
                        @elseif($invoice && $invoice->balanceDue() > 0)
                            <div class="store-row-note is-due">due ৳ {{ number_format($invoice->balanceDue(), 2) }}</div>
                        @endif
                    </span>
                    <b class="table-status {{ $invoice ? $invoice->status : $order->payment_status }}">
                        {{ ucfirst($invoice ? $invoice->status : str_replace('_', ' ', $order->payment_status)) }}
                    </b>
                    <span>
                        @if($invoice)
                            <a class="store-link" href="{{ route('invoices.view', $invoice) }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}">{{ $invoice->invoice_number }}</a>
                            <div class="store-row-note">
                                <a class="store-link" href="{{ route('invoices.view', $invoice) }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}">View</a> ·
                                <a class="store-link" href="{{ route('invoices.pdf', $invoice) }}?download=1">PDF</a>
                            </div>
                        @else
                            <span class="store-muted">—</span>
                            <div class="store-row-note">appears after payment</div>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif
@endsection
