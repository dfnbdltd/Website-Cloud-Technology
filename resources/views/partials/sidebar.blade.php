@php($storeActive = in_array($active, ['mystore', 'my-domains', 'hosting', 'invoices', 'payments']))
<aside class="domain-sidebar" id="clientSidebar">
    <a class="domain-brand" href="{{ session('customer') ? route('dashboard') : route('landing') }}">@include('partials.logo', ['gid' => 's'])</a>
    <nav>
        <small>MAIN MENU</small>
        <a {!! $active === 'dashboard' ? 'class="active"' : '' !!} href="{{ route('dashboard') }}"><b>⌂</b> Dashboard</a>
        <a {!! $active === 'domains' ? 'class="active"' : '' !!} href="{{ route('domains.index') }}"><b>◎</b> Domain Registration</a>
        <small class="nav-gap">HOSTING &amp; SERVICES</small>
        <a {!! $active === 'shared-hosting' ? 'class="active"' : '' !!} href="{{ route('hosting.shared') }}"><b>▤</b> Shared Hosting</a>
        <a href="{{ route('under.construction', 'vps-hosting') }}"><b>▤</b> VPS Hosting</a>
        <a href="{{ route('under.construction', 'dedicated-hosting') }}"><b>▤</b> Dedicated Hosting</a>
        <a href="{{ route('under.construction', 'ssl-certificate') }}"><b>◇</b> SSL Certificate</a>
        <a href="{{ route('under.construction', 'business-email') }}"><b>✉</b> Business Email</a>
        <a {!! $storeActive ? 'class="active"' : '' !!} href="{{ route('mystore') }}"><b>▦</b> My Store</a>
        <div class="subnav{{ $storeActive ? ' open' : '' }}">
            <a {!! $active === 'my-domains' ? 'class="active"' : '' !!} href="{{ route('mystore') }}#my-domains">My Domain</a>
            <a {!! $active === 'hosting' ? 'class="active"' : '' !!} href="{{ route('mystore') }}#hosting">My Hosting Services</a>
            <a {!! $active === 'invoices' ? 'class="active"' : '' !!} href="{{ route('invoices') }}">Invoices</a>
            <a {!! $active === 'payments' ? 'class="active"' : '' !!} href="{{ route('mystore') }}#payments">Payments</a>
        </div>
        <a {!! $active === 'cart' ? 'class="active"' : '' !!} href="{{ route('cart.index') }}"><b>🛒</b> Cart @if(count(session('domain_cart', [])))<i class="nav-count">{{ count(session('domain_cart', [])) }}</i>@endif</a>
        <a {!! $active === 'support' ? 'class="active"' : '' !!} href="{{ route('support') }}"><b>◔</b> Support Tickets</a>
        <small class="nav-gap">WALLET</small>
        <a {!! $active === 'balance' ? 'class="active"' : '' !!} href="{{ route('wallet.index') }}"><b>৳</b> Wallet @if(session('customer'))<i class="nav-count nav-balance">৳{{ number_format(session('customer.balance', 0)) }}</i>@endif</a>
        <a {!! $active === 'add-balance' ? 'class="active"' : '' !!} href="{{ route('wallet.index') }}#add-balance"><b>＋</b> Add balance</a>
    </nav>
    <div class="domain-side-bottom">
        @if(session('customer'))
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
        @endif
    </div>
</aside>
<button class="sidebar-backdrop" type="button" aria-label="Close navigation menu" onclick="document.getElementById('clientSidebar').classList.remove('mobile-open');document.querySelector('.sidebar-toggle').setAttribute('aria-expanded','false')"></button>
