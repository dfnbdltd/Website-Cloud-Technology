@php
    $cartCount = count(session('domain_cart', []));
    $wpPeriods = ['monthly' => 'Monthly', 'yearly' => 'Yearly', 'twoYear' => '2 Years', 'threeYear' => '3 Years'];
    $wpUnits = ['monthly' => '/ month', 'yearly' => '/ year', 'twoYear' => '/ 2 years', 'threeYear' => '/ 3 years'];
    $wpSlugs = ['monthly' => 'monthly', 'yearly' => 'yearly', 'twoYear' => 'two-year', 'threeYear' => 'three-year'];
    // A billing tab exists when at least one card shows that term (Monthly and Yearly always stay).
    $wpTabs = [];
    foreach ($wpPeriods as $wpKey => $wpLabel) {
        $wpUsed = in_array($wpKey, ['monthly', 'yearly'], true);
        foreach ($plans as $wpPlan) {
            if ($wpPlan['visibility'][$wpKey]) { $wpUsed = true; }
        }
        if ($wpUsed) { $wpTabs[$wpKey] = $wpLabel; }
    }
    $wpEmpty = count($plans) === 0;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WordPress hosting tuned for speed, security and easy updates.">
    <title>Cloud Technology | WordPress Hosting</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/hosting-nav.css') }}?v={{ md5_file(public_path('css/hosting-nav.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/hosting-wordpress.css') }}?v={{ md5_file(public_path('css/hosting-wordpress.css')) }}">
</head>
<body class="wp-body">
<div class="hosting-nav">
@include('partials.site-navbar', ['navActive' => 'hosting'])
</div>

@include('partials.hosting-rail', ['railRegion' => 'wordpress', 'railActive' => 'wordpress'])

<section class="page-banner wp-banner">
    <div class="wp-wrap">
        <p class="wp-kicker">WordPress &middot; Managed for you</p>
        <h1>WordPress Hosting</h1>
        <nav class="wp-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a><span>/</span><strong>WordPress Hosting</strong>
        </nav>
    </div>
</section>

<main class="wp-wrap wp-main">
    <div class="wp-intro">
        <h2>Simple plans for every WordPress site</h2>
        <p>Fast servers, free SSL and one-click updates. Pick a plan, choose how long you want to pay for, and start.</p>
    </div>

    @if($wpEmpty)
        <div class="hosting-empty">
            <span class="hosting-empty-pill">Plans coming soon</span>
            <h3>WordPress hosting plans are being prepared.</h3>
            <p>We are adding plans for this section. Meanwhile, you can look at our other hosting options or talk to our team.</p>
            <div class="hosting-empty-actions">
                <a class="hosting-empty-btn" href="{{ route('hosting.region', ['region' => 'local', 'type' => 'shared']) }}">See Local shared plans</a>
                <a class="hosting-empty-btn is-ghost" href="{{ route('support') }}">Contact support</a>
            </div>
        </div>
    @else
        <div class="wp-tabs" role="tablist" aria-label="Billing period">
            @foreach($wpTabs as $wpKey => $wpLabel)
                <button type="button" class="wp-tab{{ $loop->first ? ' is-active' : '' }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-period="{{ $wpKey }}">{{ $wpLabel }}</button>
            @endforeach
        </div>

        <div class="wp-grid" id="wpGrid">
            @foreach($plans as $plan)
                <article class="wp-card{{ $plan['badge'] ? ' is-featured' : '' }}" data-plan>
                    @if($plan['badge'])
                        <span class="wp-badge">{{ $plan['badge'] }}</span>
                    @endif
                    @foreach($wpTabs as $wpKey => $wpLabel)
                        @php
                            $wpShown = $plan['visibility'][$wpKey];
                            $wpActive = $plan['terms'][$wpKey] && $plan['prices'][$wpKey] !== null;
                            $wpSet = $plan['feature_sets'][$wpKey];
                        @endphp
                        <div class="wp-term" data-term="{{ $wpKey }}" data-shown="{{ $wpShown ? '1' : '0' }}" hidden>
                            <h3 class="wp-name">{{ $plan['names'][$wpKey] }}</h3>
                            @if($plan['description'] !== '')
                                <p class="wp-desc">{{ $plan['description'] }}</p>
                            @endif
                            <div class="wp-price">
                                @if($wpActive)
                                    <span class="wp-cur">&#2547;</span><span class="wp-amount">{{ rtrim(rtrim(number_format($plan['prices'][$wpKey], 2, '.', ','), '0'), '.') }}</span><small>{{ $wpUnits[$wpKey] }}</small>
                                @else
                                    <span class="wp-soon">Coming soon</span>
                                @endif
                            </div>
                            @if($wpActive)
                                <a class="wp-cta" href="{{ route('register', ['period' => $wpSlugs[$wpKey]]) }}">Get started</a>
                            @else
                                <span class="wp-cta is-disabled" aria-disabled="true">Not available yet</span>
                            @endif
                            <ul class="wp-features">
                                @foreach($wpSet['primary'] as $feature)
                                    <li class="{{ $feature['available'] ? '' : 'is-off' }}"><strong>{{ $feature['lead'] }}</strong>{{ $feature['name'] !== '' ? ' ' . $feature['name'] : '' }}</li>
                                @endforeach
                                @foreach($wpSet['extra'] as $feature)
                                    <li class="{{ $feature['available'] ? '' : 'is-off' }}">{{ $feature['text'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </article>
            @endforeach
        </div>
        <p class="wp-none" id="wpNone" hidden>No plans are shown for this billing period yet. Please choose another one.</p>
    @endif
</main>

<footer class="hosting-footer">
    <div class="hosting-footer-inner">
        <a class="hf-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a>
        <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span>
        <span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span>
    </div>
</footer>
@include('partials.chat')
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
(function(){var rail=document.querySelector('.hosting-rail');var banner=document.querySelector('.page-banner');if(!rail||!banner)return;function sync(){rail.classList.toggle('show',banner.getBoundingClientRect().bottom<120);}document.addEventListener('scroll',sync,{passive:true});window.addEventListener('resize',sync);sync();})();
(function(){
    var tabs=document.querySelectorAll('.wp-tab');
    if(!tabs.length){return;}
    var none=document.getElementById('wpNone');
    function show(period){
        var any=false;
        tabs.forEach(function(t){var on=t.dataset.period===period;t.classList.toggle('is-active',on);t.setAttribute('aria-selected',on?'true':'false');});
        document.querySelectorAll('#wpGrid [data-plan]').forEach(function(card){
            var visible=false;
            card.querySelectorAll('.wp-term').forEach(function(term){
                var on=term.dataset.term===period&&term.dataset.shown==='1';
                term.hidden=!on;
                if(on){visible=true;}
            });
            card.hidden=!visible;
            if(visible){any=true;}
        });
        if(none){none.hidden=any;}
    }
    tabs.forEach(function(t){t.addEventListener('click',function(){show(t.dataset.period);});});
    show(tabs[0].dataset.period);
})();
</script>
</body>
</html>
