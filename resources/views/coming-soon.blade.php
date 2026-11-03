{{--
    Public service page.

    `domain-transfer` is a real, interactive landing page (front-end only).
    Every other slug stays the deliberately minimal "coming soon" screen:
    one icon and one line of copy.

    Title, icon and accent still come from config/coming_soon.php.
--}}
@php($isTransfer = ($slug ?? '') === 'domain-transfer')
@php($comingCssVersion = @md5_file(public_path('css/coming-soon.css')))
@php($transferCssVersion = @md5_file(public_path('css/domain-transfer.css')))
@php($transferJsVersion = @md5_file(public_path('js/domain-transfer.js')))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $service['title'].($isTransfer ? ' — Transfer your domain' : ' — Coming soon')])
    <link rel="stylesheet" href="{{ asset('css/coming-soon.css') }}?v={{ $comingCssVersion }}">
    @if($isTransfer)
        <link rel="stylesheet" href="{{ asset('css/domain-transfer.css') }}?v={{ $transferCssVersion }}">
    @endif
</head>
<body class="cs-page{{ $isTransfer ? ' dt-public' : '' }}">
@include('partials.site-navbar', ['navActive' => null])

@if($isTransfer)
    <main class="dt-page dt-page--public">
        @include('partials.domain-transfer', ['dtVariant' => 'public'])
    </main>
@else
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
@endif

@include('partials.site-footer')
@if($isTransfer)
    <script src="{{ asset('js/domain-transfer.js') }}?v={{ $transferJsVersion }}" defer></script>
@endif
@include('partials.chat')
</body>
</html>
