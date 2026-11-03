{{--
    Public "coming soon" page for a service that is not live yet.

    Reached from the site navbar / footer links, e.g.
      /services/domain-transfer
      /services/ssl-certificate

    Deliberately minimal: one icon and one line of copy. Title, icon and
    accent all come from config/coming_soon.php, so this view stays
    presentational and adding a service needs no markup change.
--}}
@php($comingCssVersion = @md5_file(public_path('css/coming-soon.css')))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $service['title'].' — Coming soon'])
    <link rel="stylesheet" href="{{ asset('css/coming-soon.css') }}?v={{ $comingCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="cs-main" @if(!empty($service['accent'])) style="--cs-accent:{{ $service['accent'] }}" @endif>
    <section class="cs-minimal" aria-labelledby="csTitle">
        <span class="cs-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="{{ $service['icon'] }}"/>
            </svg>
        </span>

        <h1 class="cs-title" id="csTitle">{{ $service['title'] }} is coming soon</h1>
    </section>
</main>

@include('partials.site-footer')
@include('partials.chat')
</body>
</html>
