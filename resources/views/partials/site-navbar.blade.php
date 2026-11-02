{{--
    Public site navbar — the exact navbar of the home page, shared by every
    public page (top strip + sticky header). Styles live in
    public/css/site-navbar.css, scoped under #site-navbar so a page's own
    CSS can never change how it looks.

    @include('partials.site-navbar', ['navActive' => 'home' | 'domains' | 'hosting' | 'support' | null])
--}}
@php($navActive = $navActive ?? null)
@php($navCartCount = count(session('domain_cart', [])))
@php($navCssVersion = @filemtime(public_path('css/site-navbar.css')))
@php($navSelected = function ($item) use ($navActive) { return $navActive === $item ? ' class="selected"' : ''; })
<link rel="stylesheet" href="{{ asset('css/site-navbar.css') }}@if($navCssVersion)?v={{ $navCssVersion }}@endif">
<div class="site-navbar" id="site-navbar">
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a{!! $navSelected('home') !!} href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a{!! $navSelected('domains') !!} href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a class="coming-soon-trigger" href="#" data-service="Domain Transfer">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a{!! $navSelected('hosting') !!} href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a class="coming-soon-trigger" href="#" data-service="SSL Certificate">SSL Certificate</a><a class="coming-soon-trigger" href="#" data-service="Business Email">Business Email</a><a{!! $navSelected('support') !!} href="{{ route('support') }}">Support</a></nav><div class="site-account"><a class="site-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', \App\Models\Setting::get('support_phone')) }}">☎ <span>{{ \App\Models\Setting::get('support_phone') }}</span></a>@if(session('customer'))@include('partials.client-area-menu')<button class="site-user" type="button" onclick="event.stopPropagation(); document.getElementById('accountMenu').classList.toggle('open');">@if(session('customer.logo'))<img class="site-avatar" src="{{ session('customer.logo') }}" alt="">@else<span class="site-avatar">{{ session('customer.initials') ?: \App\User::initials(session('customer.name')) }}</span>@endif<span>{{ session('customer.name') }}</span> ⌄</button><div class="site-menu" id="accountMenu"><div class="site-menu-head"><strong>{{ session('customer.name') }}</strong><span>{{ session('customer.email') }}</span></div><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('mystore') }}">My Store</a><a href="{{ route('wallet.index') }}">Wallet · ৳{{ number_format(session('customer.balance', 0), 2) }}</a><a href="{{ route('invoices') }}">Invoices</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div>@else<a class="site-btn-ghost" href="{{ route('login') }}">Log in</a>@endif</div></div></header>
</div>
@include('partials.coming-soon-modal')
<script>
document.addEventListener('click',function(e){var m=document.getElementById('accountMenu');if(m&&!m.contains(e.target)){m.classList.remove('open');}});
</script>
