@php($isCustomer = (bool) session('customer'))
<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Coming soon'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">@unless($isCustomer)<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}">@endunless</head>
<body class="{{ $isCustomer ? 'domain-app' : 'site coming-page' }}">
@if($isCustomer)
@include('partials.sidebar', ['active' => 'construction', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'Under construction', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
@else
<header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><div class="site-account"><a class="site-btn-ghost" href="{{ route('login') }}">Log in</a><a class="site-btn" href="{{ route('register') }}">Sign up</a></div></div></header>
<div class="coming-band">
@endif
<div class="domain-content standalone-content">
<div class="construction-card">
<span class="construction-icon">🚧</span>
<span class="domain-kicker">UNDER CONSTRUCTION</span>
<h1 class="workspace-title">{{ $section ?? "We're building this section." }} is coming soon.</h1>
<p class="workspace-subtitle">@if($isCustomer)This part of the client area is on its way. Meanwhile you can register domains, review invoices or open a support ticket.@else This part of the site is on its way. Meanwhile you can search for a domain or create an account to get started.@endif</p>
@if($isCustomer)
<div class="quick-actions construction-actions"><a href="{{ route('domains.index') }}"><b>◎</b> Register domain</a><a href="{{ route('invoices') }}"><b>▣</b> View invoices</a><a href="{{ route('support') }}"><b>◔</b> Open ticket</a></div>
@else
<div class="quick-actions construction-actions"><a href="{{ route('landing') }}"><b>←</b> Back to home</a><a href="{{ route('login') }}"><b>↪</b> Log in</a><a href="{{ route('register') }}"><b>+</b> Create account</a></div>
@endif
</div>
</div>
@if($isCustomer)
</main>
@else
</div>
<footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
@endif
@include('partials.chat')
</body>
</html>
