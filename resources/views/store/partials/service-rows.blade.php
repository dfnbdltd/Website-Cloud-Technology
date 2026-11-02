{{--
    Rows for the hosting / SSL / business email / SMS cards and detail pages.
    Each row is one billable invoice line.

    Data: $items (App\Support\StoreInventory::service() rows), $limit (optional)
--}}
@php($rows = isset($limit) ? $items->take($limit) : $items)
@foreach($rows as $item)
    <div class="store-card-row">
        <div class="store-card-row-main">
            <strong>{{ $item['name'] }}</strong>
            <small>
                @if($item['invoice'])
                    {{ $item['invoice']->invoice_number }} ·
                @endif
                {{ $item['started_at'] ? $item['started_at']->format('d M Y') : '—' }}
                @if($item['years'] > 0)
                    · {{ $item['years'] }} year{{ $item['years'] > 1 ? 's' : '' }}
                @endif
            </small>
        </div>
        <b class="store-card-row-value">৳ {{ number_format($item['line_total'], 2) }}</b>
    </div>
@endforeach
