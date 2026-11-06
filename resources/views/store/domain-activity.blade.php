@extends('store.layout', ['pageTitle' => 'Activity · ' . $item->domain, 'navActive' => 'my-domains', 'topbarTitle' => 'My Store'])

@section('store')
    <div class="store-domain-detail-head">
        <div>
            <a class="store-back" href="{{ route('mystore.domains.details', $item->id) }}">← Back to Domain Management</a>
            <span class="domain-kicker">DOMAIN ACTIVITY</span>
            <h1 class="workspace-title">Actions for Domain Registration</h1>
            <p class="workspace-subtitle">{{ $item->domain }}</p>
        </div>
    </div>

    <section class="store-detail-panel store-full-activity store-actions-timeline">
        <div class="store-detail-section-title">
            <div><span class="domain-kicker">ALL ACTIVITY</span><h2>Activity for {{ $item->domain }}</h2></div>
        </div>
        @forelse($activities->groupBy(function ($activity) { return optional($activity['at'])->format('Y-m-d'); }) as $date => $dateActivities)
            <div class="store-action-date">{{ \Carbon\Carbon::parse($date)->format('jS M, Y') }}</div>
            @foreach($dateActivities as $activity)
                <div class="store-action-row">
                    <span class="store-action-icon" aria-hidden="true">⚙</span>
                    <div class="store-action-main">
                        <h3>{{ $activity['title'] }}</h3>
                        <p><span>DOMAIN REGISTRATION</span><b></b><a href="{{ $activity['link'] }}">{{ $activity['link_label'] }}</a></p>
                    </div>
                    <div class="store-action-meta">
                        <small>COMPLETED</small>
                        <strong>{{ optional($activity['at'])->format('jS M, Y - g:i:s a') }}</strong>
                        <span>Started: {{ optional($activity['at'])->format('jS M, Y - g:i:s a') }}</span>
                    </div>
                </div>
            @endforeach
        @empty
            <p class="store-muted">No activity is available for this domain yet.</p>
        @endforelse
    </section>
@endsection
