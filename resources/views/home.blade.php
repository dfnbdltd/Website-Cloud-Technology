@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Buy domain in Bangladesh'])<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}"></head>
<body class="site">
@include('partials.site-navbar', ['navActive' => 'home'])

<main>
    <section class="site-hero" data-hero-slider role="region" aria-roledescription="carousel" aria-label="CloudTech services">
        <div class="site-hero-track">
            <div class="site-hero-slide site-hero-slide--text is-active" role="group" aria-roledescription="slide" aria-label="Slide 1 of 3: Domain registration">
                <div class="site-wrap site-hero-grid site-hero-grid--solo">
                    <div class="site-hero-copy">
                        <h1>{{ \App\Models\Setting::get('hero_title') }}<br><em>{{ \App\Models\Setting::get('hero_highlight') }}</em></h1>
                        <p>{{ \App\Models\Setting::get('hero_text') }}</p>
                    </div>
                </div>
            </div>
            <div class="site-hero-slide site-hero-slide--text site-hero-slide--ssl" role="group" aria-roledescription="slide" aria-label="Slide 2 of 3: SSL certificates" aria-hidden="true" inert>
                <div class="site-wrap site-hero-grid site-hero-grid--solo">
                    <div class="site-hero-copy">
                        <h1>{{ \App\Models\Setting::get('ssl_title') }}<br><em>{{ \App\Models\Setting::get('ssl_highlight') }}</em></h1>
                        <p>{{ \App\Models\Setting::get('ssl_text') }}</p>
                    </div>
                </div>
            </div>
            <div class="site-hero-slide site-hero-slide--text site-hero-slide--communications" role="group" aria-roledescription="slide" aria-label="Slide 3 of 3: {{ \App\Models\Setting::get('communications_title') }}" aria-hidden="true" inert>
                <div class="site-wrap site-hero-grid site-hero-grid--solo">
                    <div class="site-hero-copy">
                        <h1>{{ \App\Models\Setting::get('communications_title') }}<br><em>{{ \App\Models\Setting::get('communications_highlight') }}</em></h1>
                        <p>{{ \App\Models\Setting::get('communications_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="site-hero-nav">
            <div class="site-hero-dots" aria-label="Choose a slide">
                <button type="button" class="is-active" aria-label="Show domain registration" aria-current="true"></button>
                <button type="button" aria-label="Show SSL certificates" aria-current="false"></button>
                <button type="button" aria-label="Show {{ \App\Models\Setting::get('communications_title') }}" aria-current="false"></button>
            </div>
        </div>
        <span class="site-hero-wave" aria-hidden="true"><svg viewBox="0 0 1440 120" preserveAspectRatio="none" focusable="false"><path d="M0,64 C240,120 480,8 720,40 C960,72 1200,116 1440,64 L1440,120 L0,120 Z" fill="#ffffff"/></svg></span>
    </section>

    <section class="site-search-band" id="domain-search">
        <div class="site-wrap">
            <div class="site-search-intro">
                <span>Find your perfect domain</span>
                <p>Search live availability and secure your name today.</p>
            </div>
            <form class="site-search" method="POST" action="{{ route('domains.search') }}" data-spinner="search">@csrf<input name="domain" placeholder="Find your perfect domain — e.g. yourbrand.com" required><button type="submit"><span class="search-button-icon" aria-hidden="true"></span> Search domain</button></form>
            <div class="site-tlds">@forelse($catalog as $tld => $plan)<a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}"><b>.{{ $tld }}</b><span>৳{{ number_format($plan['price']) }}</span></a>@empty<a href="{{ route('domains.index') }}"><b>.com</b><span>Search</span></a>@endforelse</div>
        </div>
    </section>
</main>

<section class="site-pricing site-wrap" id="pricing"><span class="site-kicker">DOMAIN PRICE LIST</span><h2>{{ \App\Models\Setting::get('pricing_title') }}</h2><p>{{ \App\Models\Setting::get('pricing_text') }}</p><div class="site-price-grid">@forelse($catalog as $tld => $plan)<article>@if(!empty($plan['popular']))<i>Popular</i>@endif<b>.{{ $tld }}</b><h3>৳ {{ number_format($plan['price']) }}<small>/yr</small></h3><p>Renews at ৳ {{ number_format($plan['renewal']) }}</p><a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}">Check availability →</a></article>@empty<article><b>.com</b><h3>—</h3><p>Live pricing unavailable right now.</p></article>@endforelse</div></section>

<section class="site-trust"><div class="site-wrap site-trust-inner">@foreach(array_filter(array_map('trim', explode(',', \App\Models\Setting::get('trust_items')))) as $item)<span>{{ $item }}</span>@endforeach</div></section>

<section class="site-cta"><div class="site-wrap"><h2>{{ \App\Models\Setting::get('cta_title') }}</h2><p>{{ \App\Models\Setting::get('cta_text') }}</p><a class="site-btn site-btn-light" href="{{ route('domains.index') }}">Start domain search →</a></div></section>
@include('partials.site-footer')
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
document.querySelectorAll('[data-hero-slider]').forEach(function(root){
    var track=root.querySelector('.site-hero-track');
    var slides=Array.prototype.slice.call(root.querySelectorAll('.site-hero-slide'));
    var dots=Array.prototype.slice.call(root.querySelectorAll('.site-hero-dots button'));
    var current=0;
    var timer;
    var initialTimer;
    var typewriterTimer;
    var reduceMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
    function prepareTypewriter(slide){
        var highlight=slide.querySelector('.site-hero-copy h1 em');
        if(!highlight){return;}
        window.clearInterval(typewriterTimer);
        var text=highlight.getAttribute('data-typewriter-text')||highlight.textContent.trim();
        highlight.setAttribute('data-typewriter-text',text);
        highlight.classList.remove('typewriter-ready');
        if(reduceMotion.matches){highlight.textContent=text;return;}
        highlight.textContent='';
        highlight.classList.add('typewriter-ready');
        var position=0;
        typewriterTimer=window.setInterval(function(){
            position+=1;
            highlight.textContent=text.slice(0,position);
            if(position>=text.length){window.clearInterval(typewriterTimer);}
        },105);
    }
    function show(index){
        current=(index+slides.length)%slides.length;
        track.style.transform='translateX(-'+(current*100)+'%)';
        slides.forEach(function(slide,i){var active=i===current;slide.classList.toggle('is-active',active);slide.setAttribute('aria-hidden',active?'false':'true');slide.toggleAttribute('inert',!active);if(active){prepareTypewriter(slide);}});
        dots.forEach(function(dot,i){var active=i===current;dot.classList.toggle('is-active',active);dot.setAttribute('aria-current',active?'true':'false');});
    }
    function stop(){window.clearInterval(timer);window.clearTimeout(initialTimer);}
    function start(){stop();if(!reduceMotion.matches){initialTimer=window.setTimeout(function(){show(current+1);timer=window.setInterval(function(){show(current+1);},5200);},7000);}}
    dots.forEach(function(dot,i){dot.addEventListener('click',function(){show(i);start();});});
    root.addEventListener('mouseenter',stop);
    root.addEventListener('mouseleave',start);
    root.addEventListener('focusin',stop);
    root.addEventListener('focusout',function(e){if(!root.contains(e.relatedTarget)){start();}});
    var touchX=null;
    root.addEventListener('touchstart',function(e){touchX=e.touches[0].clientX;},{passive:true});
    root.addEventListener('touchend',function(e){if(touchX===null){return;}var dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45){show(current+(dx<0?1:-1));start();}touchX=null;},{passive:true});
    show(0);
    start();
});
</script>
@include('partials.chat')
</body></html>
