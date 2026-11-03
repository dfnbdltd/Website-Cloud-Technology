{{--
    Client-area "coming soon" page.

    Reached from the dashboard sidebar service rows (Domain Transfer, SSL
    Certificate, Business Email). It renders inside the normal client shell
    (sidebar + topbar) and reuses the My Store empty-state card, so an upcoming
    service reads as part of the dashboard instead of popping a modal.

    Data: $service (config/coming_soon.php), $backUrl (validated local path),
          $navActive (sidebar active key).
--}}
@extends('store.layout', [
    'pageTitle' => $service['title'].' — Coming soon',
    'navActive' => $navActive,
    'topbarTitle' => 'Coming soon',
])

@section('store')
    <div class="store-page-head">
        <div class="store-page-head-copy">
            <a class="store-back" id="csBack" href="{{ $backUrl ?: '#' }}" data-back-to="{{ $backUrl ?: '' }}" data-fallback="{{ route('dashboard') }}" data-home="{{ route('landing') }}">← Go back</a>
            <span class="domain-kicker">COMING SOON</span>
            <h1 class="workspace-title">{{ $service['title'] }}</h1>
            <p class="workspace-subtitle">{{ $service['message'] }}</p>
        </div>
        <div class="store-page-head-aside">
            <span class="store-count-pill">Coming soon</span>
        </div>
    </div>

    <div class="store-empty tone-{{ $service['tone'] }}">
        <span class="store-empty-icon" aria-hidden="true">{{ $service['glyph'] }}</span>
        <h2>{{ $service['heading'] }}</h2>
        <p>{{ $service['message'] }}</p>

        @if(!empty($service['highlights']))
            <ul class="store-coming-points">
                @foreach($service['highlights'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        @endif

        <div class="store-empty-actions">
            <a class="store-primary" href="{{ $backUrl ?: '#' }}" data-back-to="{{ $backUrl ?: '' }}">← Go back</a>
            <a class="store-ghost-link" href="{{ route('support') }}">Talk to our team</a>
        </div>
    </div>
@endsection

<script>
(function () {
    var back = document.getElementById('csBack');
    if (!back) { return; }

    function localPath(url) {
        try {
            var u = new URL(url, window.location.origin);
            if (u.origin === window.location.origin) { return u.pathname + u.search + u.hash; }
        } catch (e) { /* ignore */ }
        return '';
    }

    var target = back.getAttribute('data-back-to');
    if (!target && document.referrer) { target = localPath(document.referrer); }
    if (!target) { target = back.getAttribute('data-fallback') || back.getAttribute('data-home') || '/'; }

    back.setAttribute('href', target);

    // A matching "Go back" button inside the card keeps the same destination.
    document.querySelectorAll('[data-back-to]').forEach(function (el) {
        if (el !== back) { el.setAttribute('href', target); }
    });
}());
</script>
