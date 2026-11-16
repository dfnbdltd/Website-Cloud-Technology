@extends('store.layout', ['pageTitle' => 'Activity · ' . $item->domain, 'navActive' => 'my-domains', 'topbarTitle' => 'My Store'])

@section('store')
    <div class="store-domain-detail-head">
        <div>
            <a class="store-back" href="{{ route('mystore.domains.details', $item->id) }}">← Back to Domain Management</a>
            <span class="domain-kicker">DOMAIN ACTIVITY</span>
            <h1 class="workspace-title">Actions for Domain {{ !empty($isTransfer) ? 'Transfer' : 'Registration' }}</h1>
            <p class="workspace-subtitle">{{ $item->domain }}</p>
        </div>
    </div>

    <section class="store-detail-panel store-full-activity store-actions-timeline">
        <div class="store-detail-section-title">
            <div><span class="domain-kicker">ALL ACTIVITY</span><h2>Activity for {{ $item->domain }}</h2></div>
        </div>
        @if($activitiesUnavailable)
            <p class="store-muted store-actions-empty">Activity is temporarily unavailable. Please try again shortly.</p>
        @else
            @forelse($activities->groupBy(function ($activity) { return $activity['at'] ? $activity['at']->format('Y-m-d') : 'undated'; }) as $date => $dateActivities)
                <div class="store-action-date">{{ $date === 'undated' ? 'Date unavailable' : \Carbon\Carbon::parse($date)->format('jS M, Y') }}</div>
                @foreach($dateActivities as $activity)
                    <div class="store-action-row">
                        <span class="store-action-icon" aria-hidden="true">⚙</span>
                        <div class="store-action-main">
                            <h3>{{ $activity['title'] }}</h3>
                            @if($activity['description'] !== '')
                                <small class="store-action-desc is-{{ $activity['state'] }}">{{ $activity['description'] }}</small>
                            @endif
                            <p><span>{{ !empty($isTransfer) ? 'DOMAIN TRANSFER' : 'DOMAIN REGISTRATION' }}</span><b></b><a href="{{ $orderUrl }}">View Order</a></p>
                        </div>
                        <div class="store-action-meta">
                            <small class="store-action-state is-{{ $activity['state'] }}">{{ strtoupper($activity['state_label']) }}</small>
                            <strong>{{ $activity['completed_at'] ? $activity['completed_at']->format('jS M, Y - g:i:s a') : '—' }}</strong>
                            <span>Started: {{ $activity['started_at'] ? $activity['started_at']->format('jS M, Y - g:i:s a') : '—' }}</span>
                        </div>
                    </div>
                @endforeach
            @empty
                <p class="store-muted store-actions-empty">No activity</p>
            @endforelse
        @endif
    </section>

    @if($orderHistory->isNotEmpty())
        <section class="store-detail-panel store-order-history">
            <div class="store-detail-section-title">
                <div><span class="domain-kicker">YOUR ORDER</span><h2>Order &amp; payment history</h2></div>
            </div>
            @foreach($orderHistory as $entry)
                <div class="store-order-history-row">
                    <div>
                        <strong>{{ $entry['title'] }}</strong>
                        <p>{{ $entry['description'] }}</p>
                    </div>
                    <div class="store-order-history-meta">
                        <span>{{ optional($entry['at'])->format('jS M, Y - g:i a') }}</span>
                        <a href="{{ $entry['link'] }}">{{ $entry['link_label'] }}</a>
                    </div>
                </div>
            @endforeach
        </section>
    @endif
@endsection
