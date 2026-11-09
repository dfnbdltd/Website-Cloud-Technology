@extends('store.layout', ['pageTitle' => $section['title'], 'navActive' => $section['nav'], 'topbarTitle' => 'My Store'])

@section('store')
    @include('store.partials.page-head', ['section' => $section, 'count' => $items->count(), 'countLabel' => 'service'])

    @forelse($items as $item)
        <article class="store-item">
            <header class="store-item-head">
                <span class="store-item-icon tone-{{ $section['tone'] }}" aria-hidden="true">{{ $section['icon'] }}</span>
                <div class="store-item-title">
                    <h2>{{ $item['name'] }}</h2>
                    <p>
                        {{ ucfirst(str_replace('-', ' ', $item['item_type'])) }}
                        @if($item['invoice'])
                            · invoice <b>{{ $item['invoice']->invoice_number }}</b>
                        @endif
                    </p>
                </div>
                <span class="table-status {{ $item['invoice'] ? $item['invoice']->status : 'paid' }}">
                    {{ ucfirst($item['invoice'] ? $item['invoice']->status : 'active') }}
                </span>
            </header>

            <div class="store-facts">
                <div class="store-fact">
                    <small>STARTED</small>
                    <strong>{{ $item['started_at'] ? $item['started_at']->format('d M Y') : '—' }}</strong>
                    <em>paid on</em>
                </div>
                <div class="store-fact">
                    <small>{{ $item['years'] > 0 ? 'EXPIRES' : 'TERM' }}</small>
                    <strong>
                        @if($item['expires_at'])
                            {{ $item['expires_at']->format('d M Y') }}
                        @else
                            {{ $item['quantity'] }} × {{ $item['years'] > 0 ? $item['years'] . ' year' : 'one-time' }}
                        @endif
                    </strong>
                    <em>{{ $item['years'] > 0 ? $item['years'] . ' year' . ($item['years'] > 1 ? 's' : '') . ' term' : 'no renewal' }}</em>
                </div>
                <div class="store-fact">
                    <small>QUANTITY</small>
                    <strong>{{ $item['quantity'] }}</strong>
                    <em>৳ {{ number_format($item['unit_price'], 2) }} each</em>
                </div>
                <div class="store-fact">
                    <small>AMOUNT</small>
                    <strong>৳ {{ number_format($item['line_total'], 2) }}</strong>
                    <em>incl. VAT</em>
                </div>
            </div>

            <footer class="store-item-foot">
                <div class="store-item-links">
                    @if($item['invoice'])
                        <a href="{{ route('invoices.view', $item['invoice']) }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}">Invoice {{ $item['invoice']->invoice_number }}</a>
                        <a href="{{ route('invoices.pdf', $item['invoice']) }}?download=1">Download PDF</a>
                    @endif
                    @if(!empty($section['cta']['route']))
                        <a href="{{ route($section['cta']['route'], $section['cta']['params']) }}">{{ $section['cta']['label'] }}</a>
                    @endif
                </div>
            </footer>
        </article>
    @empty
        @include('store.partials.empty', ['section' => $section])
    @endforelse
@endsection
