@php($storeActive = in_array($active, ['mystore', 'my-domains', 'hosting', 'invoices', 'payments']))
@php($domainMenuActive = in_array($active, ['domains', 'domain-transfer']))
@php($hostingMenuActive = in_array($active, ['shared-hosting', 'hosting-shared', 'hosting-vps', 'hosting-dedicated']))
<aside class="domain-sidebar" id="clientSidebar">
    <a class="domain-brand" href="{{ route('landing') }}" title="Go to home page" aria-label="Go to home page">@include('partials.logo', ['gid' => 's'])</a>
    <nav>
        <a href="{{ route('landing') }}"><b>⌂</b> Go to home</a>
        <a {!! $active === 'dashboard' ? 'class="active"' : '' !!} href="{{ route('dashboard') }}"><b>◈</b> Dashboard</a>
        <a href="#" {!! $domainMenuActive ? 'class="open domain-registration-toggle"' : 'class="domain-registration-toggle"' !!} aria-expanded="{{ $domainMenuActive ? 'true' : 'false' }}" aria-controls="domainRegistrationSubnav" onclick="event.preventDefault();var s=document.getElementById('domainRegistrationSubnav');var open=s.classList.toggle('open');this.classList.toggle('open',open);this.setAttribute('aria-expanded',open);"><b>◎</b> Domains <i class="nav-caret">∨</i></a>
        <div class="subnav{{ $domainMenuActive ? ' open' : '' }}" id="domainRegistrationSubnav">
            <a {!! $active === 'domains' ? 'class="active"' : '' !!} href="{{ route('domains.index') }}">Domain Registration</a>
            <a {!! $active === 'domain-transfer' ? 'class="active"' : '' !!} href="{{ route('under.construction', 'domain-transfer') }}">Domain Transfer</a>
        </div>
        <hr class="nav-divider">
        <style>.domain-sidebar nav>a.hosting-package-toggle,.domain-sidebar nav>a.domain-registration-toggle{white-space:nowrap}.domain-sidebar nav>a .nav-count,.domain-sidebar nav>a .nav-caret{flex:none}.domain-sidebar nav>a .nav-caret{display:inline-block;font-size:16px;font-weight:800;font-style:normal;color:inherit;transition:transform .2s}.domain-sidebar nav>a.open .nav-caret{transform:rotate(180deg)}</style>
        <a href="#" {!! $hostingMenuActive ? 'class="active open hosting-package-toggle"' : 'class="hosting-package-toggle"' !!} aria-expanded="{{ $hostingMenuActive ? 'true' : 'false' }}" aria-controls="hostingPackageSubnav" onclick="event.preventDefault();var s=document.getElementById('hostingPackageSubnav');var open=s.classList.toggle('open');this.classList.toggle('open',open);this.setAttribute('aria-expanded',open);"><b>▤</b> Hosting Package <i class="nav-count">3</i><i class="nav-caret">∨</i></a>
        <div class="subnav{{ $hostingMenuActive ? ' open' : '' }}" id="hostingPackageSubnav">
            <a {!! in_array($active, ['shared-hosting', 'hosting-shared']) ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'shared') }}"><b>▤</b> Shared Hosting</a>
            <a {!! $active === 'hosting-vps' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'vps') }}"><b>▤</b> VPS Hosting</a>
            <a {!! $active === 'hosting-dedicated' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'dedicated') }}"><b>▤</b> Dedicated Hosting</a>
        </div>
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
        <hr class="nav-divider">
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
