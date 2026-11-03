{{--
    Public "coming soon" page for a service that is not live yet.

    Reached from the site navbar / footer links, e.g.
      /services/domain-transfer
      /services/ssl-certificate

    Copy, icon and accent all come from config/coming_soon.php, so this view
    stays presentational and adding a service needs no markup change.

    "Go back" prefers the `back` query string (validated as a local path in the
    route). When it is absent — a direct visit or a shared link — it steps back
    through history, and only falls back to the home page if there is nowhere
    to return to. That is why the anchor works even with JS disabled.
--}}
@php($comingCssVersion = @filemtime(public_path('css/coming-soon.css')))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $service['title'].' — Coming soon'])
    <link rel="stylesheet" href="{{ asset('css/coming-soon.css') }}?v={{ $comingCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="cs-main" @if(!empty($service['accent'])) style="--cs-accent:{{ $service['accent'] }}" @endif>
    <section class="cs-card" aria-labelledby="csTitle">
        <span class="cs-glow" aria-hidden="true"></span>

        <span class="cs-badge" aria-hidden="true">
            @include('partials.logo', ['gid' => 'cs'])
        </span>

        <p class="cs-eyebrow">Coming soon</p>
        <h1 class="cs-title" id="csTitle">{{ $service['heading'] }}</h1>
        <p class="cs-message">{{ $service['message'] }}</p>

        @if(!empty($service['highlights']))
            <ul class="cs-points">
                @foreach($service['highlights'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        @endif

        <div class="cs-actions">
            <a class="cs-btn cs-btn-primary" id="csBack" href="{{ $backUrl ?: '#' }}" data-back-to="{{ $backUrl ?: '' }}" data-home="{{ route('landing') }}">
                <span aria-hidden="true">←</span> Go back
            </a>
            @isset($service['cta'])
                <a class="cs-btn cs-btn-ghost" href="{{ route($service['cta']['route']) }}">{{ $service['cta']['label'] }}</a>
            @endisset
        </div>

        <p class="cs-note">
            Need it sooner? <a href="{{ route('support') }}">Talk to our team</a>.
        </p>
    </section>
</main>

@include('partials.site-footer')

<script>
(function () {
    var back = document.getElementById('csBack');
    if (!back) { return; }

    var target = back.getAttribute('data-back-to');
    var home = back.getAttribute('data-home');

    // A real referring page wins over everything: it is exactly where the
    // visitor was before they clicked the coming-soon link.
    if (!target && document.referrer) {
        try {
            var ref = new URL(document.referrer);
            if (ref.origin === window.location.origin && ref.pathname !== window.location.pathname) {
                target = ref.pathname + ref.search + ref.hash;
            }
        } catch (e) { /* malformed referrer: ignore */ }
    }

    if (target) {
        back.setAttribute('href', target);
        return;
    }

    // No usable referrer (direct visit / shared link). If this tab has real
    // history to step back through, do that and keep the link useful.
    if (window.history.length > 1) {
        back.setAttribute('href', home);
        back.addEventListener('click', function (event) {
            event.preventDefault();
            window.history.back();
        });
    }
}());
</script>
@include('partials.chat')
</body>
</html>
