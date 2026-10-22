@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Buy domain in Bangladesh'])<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}"></head>
<body class="site">
<div class="site-top"><div class="site-wrap site-top-inner"><div class="site-top-links"><a href="{{ route('under.construction', 'blog') }}">Blog</a><a href="{{ route('under.construction', 'offers') }}">Offers</a><span>☎ {{ \App\Models\Setting::get('support_phone') }}</span></div><div class="site-top-links"><a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ $cartCount }}</b></a></div></div></div>
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a class="selected" href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a href="{{ route('under.construction', 'domain-transfer') }}">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a href="{{ route('under.construction', 'email-hosting') }}">Email</a><a href="{{ route('under.construction', 'sms-service') }}">SMS Service</a><a href="{{ route('support') }}">Support</a></nav><div class="site-account">@if(session('customer'))@include('partials.client-area-menu')<button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">@if(session('customer.logo'))<img class="site-avatar" src="{{ session('customer.logo') }}" alt="">@else<span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>@endif<span>{{ session('customer.name') }}</span> ⌄</button><div class="site-menu" id="accountMenu"><div class="site-menu-head"><strong>{{ session('customer.name') }}</strong><span>{{ session('customer.email') }}</span></div><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('mystore') }}">My Store</a><a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a><a href="{{ route('invoices') }}">Invoices</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>@else<a class="site-btn-ghost" href="{{ route('login') }}">Log in</a>@endif</div></div></header>

<main>
    <section class="site-hero" data-hero-slider role="region" aria-roledescription="carousel" aria-label="CloudTech services">
        <div class="site-hero-track">
            <div class="site-hero-slide site-hero-slide--text is-active" role="group" aria-roledescription="slide" aria-label="Slide 1 of 2: Domain registration">
                <div class="site-wrap site-hero-grid site-hero-grid--solo">
                    <div class="site-hero-copy">
                        <span class="site-hero-eyebrow">Domain registration</span>
                        <h1>{{ \App\Models\Setting::get('hero_title') }}<br><em>{{ \App\Models\Setting::get('hero_highlight') }}</em></h1>
                        <p>{{ \App\Models\Setting::get('hero_text') }}</p>
                        <div class="site-hero-actions">
                            <a class="site-btn" href="#domain-search">Search a domain <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg></a>
                            <a class="site-btn-ghost" href="#pricing">See pricing</a>
                        </div>
                        @if(count($heroProof) > 0)
                        <div class="site-hero-proof">
                            @foreach($heroProof as $proof)
                            <span>{{ $proof }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="site-hero-slide site-hero-slide--text site-hero-slide--ssl" role="group" aria-roledescription="slide" aria-label="Slide 2 of 2: SSL certificates" aria-hidden="true" inert>
                <div class="site-wrap site-hero-grid site-hero-grid--solo">
                    <div class="site-hero-copy">
                        <span class="site-hero-eyebrow">SSL certificates</span>
                        <h1>{{ \App\Models\Setting::get('ssl_title') }}<br><em>{{ \App\Models\Setting::get('ssl_highlight') }}</em></h1>
                        <p>{{ \App\Models\Setting::get('ssl_text') }}</p>
                        <div class="site-hero-actions">
                            <a class="site-btn" href="{{ route('under.construction', 'ssl-certificate') }}">Get SSL certificate <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg></a>
                            <a class="site-btn-ghost" href="#pricing">Compare plans</a>
                        </div>
                        <div class="site-hero-proof">
                            <span>256-bit encryption</span>
                            <span>Free installation</span>
                            <span>Auto-renew included</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="site-hero-nav">
            <div class="site-hero-dots" aria-label="Choose a slide">
                <button type="button" class="is-active" aria-label="Show domain registration" aria-current="true"></button>
                <button type="button" aria-label="Show SSL certificates" aria-current="false"></button>
            </div>
        </div>
        <span class="site-hero-wave" aria-hidden="true"><svg viewBox="0 0 1440 120" preserveAspectRatio="none" focusable="false"><path d="M0,64 C240,120 480,8 720,40 C960,72 1200,116 1440,64 L1440,120 L0,120 Z" fill="#ffffff"/></svg></span>
    </section>

    <section class="site-search-band" id="domain-search">
        <div class="site-wrap">
            <form class="site-search" method="POST" action="{{ route('domains.search') }}">@csrf<input name="domain" placeholder="Find your perfect domain — e.g. yourbrand.com" required><button type="submit">Search</button></form>
            <div class="site-tlds">@forelse($catalog as $tld => $plan)<a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}"><b>.{{ $tld }}</b><span>৳{{ number_format($plan['price']) }}</span></a>@empty<a href="{{ route('domains.index') }}"><b>.com</b><span>Search</span></a>@endforelse</div>
        </div>
    </section>
</main>

<section class="site-pricing site-wrap" id="pricing"><span class="site-kicker">DOMAIN PRICE LIST</span><h2>{{ \App\Models\Setting::get('pricing_title') }}</h2><p>{{ \App\Models\Setting::get('pricing_text') }}</p><div class="site-price-grid">@forelse($catalog as $tld => $plan)<article>@if(!empty($plan['popular']))<i>Popular</i>@endif<b>.{{ $tld }}</b><h3>৳ {{ number_format($plan['price']) }}<small>/yr</small></h3><p>Renews at ৳ {{ number_format($plan['renewal']) }}</p><a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}">Check availability →</a></article>@empty<article><b>.com</b><h3>—</h3><p>Live pricing unavailable right now.</p></article>@endforelse</div></section>

<section class="site-trust"><div class="site-wrap site-trust-inner">@foreach(array_filter(array_map('trim', explode(',', \App\Models\Setting::get('trust_items')))) as $item)<span>{{ $item }}</span>@endforeach</div></section>

<section class="site-cta"><div class="site-wrap"><h2>{{ \App\Models\Setting::get('cta_title') }}</h2><p>{{ \App\Models\Setting::get('cta_text') }}</p><a class="site-btn site-btn-light" href="{{ route('domains.index') }}">Start domain search →</a></div></section>
<footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
document.querySelectorAll('[data-hero-slider]').forEach(function(root){
    var track=root.querySelector('.site-hero-track');
    var slides=Array.prototype.slice.call(root.querySelectorAll('.site-hero-slide'));
    var dots=Array.prototype.slice.call(root.querySelectorAll('.site-hero-dots button'));
    var current=0;
    var timer;
    function show(index){
        current=(index+slides.length)%slides.length;
        track.style.transform='translateX(-'+(current*100)+'%)';
        slides.forEach(function(slide,i){var active=i===current;slide.classList.toggle('is-active',active);slide.setAttribute('aria-hidden',active?'false':'true');slide.toggleAttribute('inert',!active);});
        dots.forEach(function(dot,i){var active=i===current;dot.classList.toggle('is-active',active);dot.setAttribute('aria-current',active?'true':'false');});
    }
    function stop(){window.clearInterval(timer);}
    function start(){stop();if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){timer=window.setInterval(function(){show(current+1);},6000);}}
    dots.forEach(function(dot,i){dot.addEventListener('click',function(){show(i);start();});});
    root.addEventListener('mouseenter',stop);
    root.addEventListener('mouseleave',start);
    root.addEventListener('focusin',stop);
    root.addEventListener('focusout',function(e){if(!root.contains(e.relatedTarget)){start();}});
    var touchX=null;
    root.addEventListener('touchstart',function(e){touchX=e.touches[0].clientX;},{passive:true});
    root.addEventListener('touchend',function(e){if(touchX===null){return;}var dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45){show(current+(dx<0?1:-1));start();}touchX=null;},{passive:true});
    start();
});
</script>
@include('partials.chat')
</body></html>
