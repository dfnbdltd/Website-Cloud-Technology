@php($storeActive = in_array($active, ['mystore', 'my-domains', 'hosting', 'invoices', 'payments']))
@php($domainMenuActive = in_array($active, ['domains', 'domain-transfer']))
@php($hostingMenuActive = in_array($active, ['shared-hosting', 'hosting-shared', 'hosting-vps', 'hosting-dedicated']))
<aside class="domain-sidebar" id="clientSidebar">
    <a class="domain-brand" href="{{ route('landing') }}" title="Go to home page" aria-label="Go to home page">@include('partials.logo', ['gid' => 's'])<span class="sidebar-brand-name">Cloud <em>Technology</em></span></a>
    <nav>
        <span class="sidebar-nav-label">Workspace</span>
        <a data-sidebar-label="Home" href="{{ route('landing') }}"><b>⌂</b><span>Home</span></a>
        <a data-sidebar-label="Dashboard" {!! $active === 'dashboard' ? 'class="active"' : '' !!} href="{{ route('dashboard') }}"><b>◈</b><span>Dashboard</span></a>
        <span class="sidebar-nav-label sidebar-nav-label-services">Services</span>
        <a data-sidebar-label="Domains" href="#" {!! $domainMenuActive ? 'class="active open domain-registration-toggle service-group-start service-nav-item"' : 'class="domain-registration-toggle service-group-start service-nav-item"' !!} aria-expanded="{{ $domainMenuActive ? 'true' : 'false' }}" aria-controls="domainRegistrationSubnav" onclick="event.preventDefault();var s=document.getElementById('domainRegistrationSubnav');var open=s.classList.toggle('open');this.classList.toggle('open',open);this.classList.toggle('active',open);this.setAttribute('aria-expanded',open);"><b>◎</b><span>Domains</span><i class="nav-caret nav-toggle" aria-hidden="true"></i></a>
        <div class="subnav{{ $domainMenuActive ? ' open' : '' }}" id="domainRegistrationSubnav">
            <a data-sidebar-label="Domain Registration" {!! $active === 'domains' ? 'class="active"' : '' !!} href="{{ route('domains.index') }}"><b aria-hidden="true">◇</b><span>Domain Registration</span></a>
            <a data-sidebar-label="Domain Transfer" class="coming-soon-trigger" href="#" data-service="Domain Transfer"><b aria-hidden="true">◇</b><span>Domain Transfer</span></a>
        </div>
        <a data-sidebar-label="Web Hosting" href="#" {!! $hostingMenuActive ? 'class="active open hosting-package-toggle service-nav-item"' : 'class="hosting-package-toggle service-nav-item"' !!} aria-expanded="{{ $hostingMenuActive ? 'true' : 'false' }}" aria-controls="hostingPackageSubnav" onclick="event.preventDefault();var s=document.getElementById('hostingPackageSubnav');var open=s.classList.toggle('open');this.classList.toggle('open',open);this.classList.toggle('active',open);this.setAttribute('aria-expanded',open);"><b>▤</b><span>Web Hosting</span><i class="nav-caret nav-toggle" aria-hidden="true"></i></a>
        <div class="subnav{{ $hostingMenuActive ? ' open' : '' }}" id="hostingPackageSubnav">
            <a data-sidebar-label="Shared Hosting" {!! in_array($active, ['shared-hosting', 'hosting-shared']) ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'shared') }}"><b aria-hidden="true">▤</b><span>Shared Hosting</span></a>
            <a data-sidebar-label="VPS Hosting" {!! $active === 'hosting-vps' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'vps') }}"><b aria-hidden="true">▤</b><span>VPS Hosting</span></a>
            <a data-sidebar-label="Dedicated Hosting" {!! $active === 'hosting-dedicated' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'dedicated') }}"><b aria-hidden="true">▤</b><span>Dedicated Hosting</span></a>
        </div>
        <a data-sidebar-label="SSL Certificate" class="service-nav-item coming-soon-trigger" href="#" data-service="SSL Certificate"><b>◇</b><span>SSL Certificate</span></a>
        <a data-sidebar-label="Business Email" class="service-group-end service-nav-item coming-soon-trigger" href="#" data-service="Business Email"><b>✉</b><span>Business Email</span></a>
        <span class="sidebar-nav-label sidebar-nav-label-store">Account</span>
        <a data-sidebar-label="My Store" {!! $storeActive ? 'class="active"' : '' !!} href="{{ route('mystore') }}"><b>▦</b><span>My Store</span></a>
        <a data-sidebar-label="Cart" {!! $active === 'cart' ? 'class="active"' : '' !!} href="{{ route('cart.index') }}"><b>🛒</b><span>Cart</span> @if(count(session('domain_cart', [])))<i class="nav-count">{{ count(session('domain_cart', [])) }}</i>@endif</a>
        <a data-sidebar-label="Wallet" {!! $active === 'balance' ? 'class="active"' : '' !!} href="{{ route('wallet.index') }}"><b>৳</b><span>Wallet</span> @if(session('customer'))<i class="nav-count nav-balance">৳{{ number_format(session('customer.balance', 0)) }}</i>@endif</a>
    </nav>
    <div class="domain-side-bottom">
        @if(session('customer'))
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">↪ <span>Sign out</span></button></form>
        @endif
    </div>
</aside>
<button class="sidebar-backdrop" type="button" aria-label="Close navigation menu" onclick="document.getElementById('clientSidebar').classList.remove('mobile-open');document.querySelector('.sidebar-toggle').setAttribute('aria-expanded','false')"></button>
@include('partials.coming-soon-modal')
