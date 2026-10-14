@php($cartCount = count(session('domain_cart', [])))
<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Buy domain in Bangladesh'])<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}"></head>
<body class="site">
<div class="site-top"><div class="site-wrap site-top-inner"><div class="site-top-links"><a href="{{ route('under.construction', 'blog') }}">Blog</a><a href="{{ route('under.construction', 'offers') }}">Offers</a><span>☎ {{ \App\Models\Setting::get('support_phone') }}</span></div><div class="site-top-links"><a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ $cartCount }}</b></a></div></div></div>
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a class="selected" href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a href="{{ route('under.construction', 'domain-transfer') }}">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('under.construction', 'vps-hosting') }}">VPS Hosting</a><a href="{{ route('under.construction', 'dedicated-hosting') }}">Dedicated Hosting</a></div></div><a href="{{ route('under.construction', 'email-hosting') }}">Email</a><a href="{{ route('under.construction', 'sms-service') }}">SMS Service</a><a href="{{ route('support') }}">Support</a></nav><div class="site-account">@if(session('customer'))@include('partials.client-area-menu')<button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">@if(session('customer.logo'))<img class="site-avatar" src="{{ session('customer.logo') }}" alt="">@else<span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>@endif<span>{{ session('customer.name') }}</span> ⌄</button><div class="site-menu" id="accountMenu"><div class="site-menu-head"><strong>{{ session('customer.name') }}</strong><span>{{ session('customer.email') }}</span></div><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('mystore') }}">My Store</a><a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a><a href="{{ route('invoices') }}">Invoices</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>@else<a class="site-btn-ghost" href="{{ route('login') }}">Log in</a>@endif</div></div></header>

@php($slides = [
    ['image' => 'Modern Domain Registration Banner.png', 'alt' => 'Modern domain registration'],
    ['image' => 'Professional Business Email Workspace.png', 'alt' => 'Professional business email workspace'],
    ['image' => 'Modern VPS Hosting Data Center Banner.png', 'alt' => 'Modern VPS hosting data center'],
    ['image' => 'SSL Certificate Security Banner.png', 'alt' => 'SSL certificate security'],
    ['image' => 'Fast, Reliable SMS Messaging.png', 'alt' => 'Fast and reliable SMS messaging'],
    ['image' => 'Dedicated Hosting_ Total Control.png', 'alt' => 'Dedicated hosting with total control'],
])
<main>
    <section class="site-carousel" data-carousel role="region" aria-label="CloudTech services" aria-roledescription="carousel">
        @foreach($slides as $index => $slide)
            <div class="site-carousel-slide{{ $index === 0 ? ' is-active' : '' }}" aria-hidden="{{ $index === 0 ? 'false' : 'true' }}" {{ $index === 0 ? '' : 'hidden' }}>
                <img src="{{ asset('image/' . $slide['image']) }}" alt="{{ $slide['alt'] }}" {{ $index === 0 ? 'fetchpriority=high' : 'loading=lazy' }}>
            </div>
        @endforeach
        <button class="site-carousel-control site-carousel-prev" type="button" aria-label="Previous slide">‹</button>
        <button class="site-carousel-control site-carousel-next" type="button" aria-label="Next slide">›</button>
        <div class="site-carousel-dots" aria-label="Choose a slide">
            @foreach($slides as $index => $slide)
                <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" aria-label="Show slide {{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    </section>

    <section class="site-hero-content">
        <div class="site-wrap">
            <div class="site-hero-copy">
                <h1>{{ \App\Models\Setting::get('hero_title') }}<br><em>{{ \App\Models\Setting::get('hero_highlight') }}</em></h1>
                <p>{{ \App\Models\Setting::get('hero_text') }}</p>
                <form class="site-search" method="POST" action="{{ route('domains.search') }}">@csrf<input name="domain" placeholder="Find your perfect domain — e.g. yourbrand.com" required><button type="submit">Search</button></form>
                <div class="site-tlds">@forelse($catalog as $tld => $plan)<a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}"><b>.{{ $tld }}</b><span>৳{{ number_format($plan['price']) }}</span></a>@empty<a href="{{ route('domains.index') }}"><b>.com</b><span>Search</span></a>@endforelse</div>
            </div>
        </div>
    </section>
</main>

<section class="site-pricing site-wrap" id="pricing"><span class="site-kicker">DOMAIN PRICE LIST</span><h2>{{ \App\Models\Setting::get('pricing_title') }}</h2><p>{{ \App\Models\Setting::get('pricing_text') }}</p><div class="site-price-grid">@forelse($catalog as $tld => $plan)<article>@if(!empty($plan['popular']))<i>Popular</i>@endif<b>.{{ $tld }}</b><h3>৳ {{ number_format($plan['price']) }}<small>/yr</small></h3><p>Renews at ৳ {{ number_format($plan['renewal']) }}</p><a href="{{ route('domains.index', ['domain' => 'yourbrand.' . $tld]) }}">Check availability →</a></article>@empty<article><b>.com</b><h3>—</h3><p>Live pricing unavailable right now.</p></article>@endforelse</div></section>

<section class="site-trust"><div class="site-wrap site-trust-inner">@foreach(array_filter(array_map('trim', explode(',', \App\Models\Setting::get('trust_items')))) as $item)<span>{{ $item }}</span>@endforeach</div></section>

<section class="site-cta"><div class="site-wrap"><h2>{{ \App\Models\Setting::get('cta_title') }}</h2><p>{{ \App\Models\Setting::get('cta_text') }}</p><a class="site-btn site-btn-light" href="{{ route('domains.index') }}">Start domain search →</a></div></section>
<footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
document.querySelectorAll('[data-carousel]').forEach(function(carousel){
    var slides=Array.prototype.slice.call(carousel.querySelectorAll('.site-carousel-slide'));
    var current=0;
    var timer;
    function showSlide(index){
        current=(index+slides.length)%slides.length;
        slides.forEach(function(slide,i){var active=i===current;slide.classList.toggle('is-active',active);slide.hidden=!active;slide.setAttribute('aria-hidden',active?'false':'true');});
        carousel.querySelectorAll('.site-carousel-dots button').forEach(function(dot,i){var active=i===current;dot.classList.toggle('is-active',active);dot.setAttribute('aria-current',active?'true':'false');});
    }
    function stop(){window.clearInterval(timer);}
    function start(){stop();if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches){timer=window.setInterval(function(){showSlide(current+1);},5000);}}
    carousel.querySelectorAll('.site-carousel-prev').forEach(function(button){button.addEventListener('click',function(){showSlide(current-1);start();});});
    carousel.querySelectorAll('.site-carousel-next').forEach(function(button){button.addEventListener('click',function(){showSlide(current+1);start();});});
    carousel.querySelectorAll('.site-carousel-dots').forEach(function(group){group.querySelectorAll('button').forEach(function(dot,i){dot.addEventListener('click',function(){showSlide(i);start();});});});
    carousel.addEventListener('mouseenter',stop);
    carousel.addEventListener('mouseleave',start);
    carousel.addEventListener('focusin',stop);
    carousel.addEventListener('focusout',function(e){if(!carousel.contains(e.relatedTarget)){start();}});
    start();
});
</script>
@include('partials.chat')
</body></html>
