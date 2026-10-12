<details class="site-client-area">
    <summary>Client Area</summary>
    <nav class="site-client-area-menu" aria-label="Client Area">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('domains.index') }}">Domain Registration</a>
        <a href="{{ route('hosting.shared') }}">Shared Hosting</a>
        <a href="{{ route('under.construction', 'vps-hosting') }}">VPS Hosting</a>
        <a href="{{ route('under.construction', 'dedicated-hosting') }}">Dedicated Hosting</a>
        <a href="{{ route('under.construction', 'ssl-certificate') }}">SSL Certificate</a>
        <a href="{{ route('under.construction', 'business-email') }}">Business Email</a>
        <a href="{{ route('mystore') }}">My Store</a>
        <a href="{{ route('cart.index') }}">Cart <b class="site-count">{{ count(session('domain_cart', [])) }}</b></a>
        <a href="{{ route('support') }}">Support Tickets</a>
        <a href="{{ route('wallet.index') }}">Wallet</a>
    </nav>
</details>
