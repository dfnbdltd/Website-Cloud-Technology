<details class="site-client-area">
    <summary>Client Area</summary>
    <nav class="site-client-area-menu" aria-label="Client Area">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <details class="site-client-area-group">
            <summary>My Store</summary>
            <div class="site-client-area-submenu">
                <a href="{{ route('mystore') }}">All Items</a>
                <a href="{{ route('mystore.domains') }}">Domain</a>
                <a href="{{ route('mystore.hosting') }}">Web Hosting</a>
                <a href="{{ route('mystore.service', 'ssl') }}">SSL Certificate</a>
                <a href="{{ route('mystore.service', 'email') }}">Business Email</a>
                <a href="{{ route('mystore.service', 'sms') }}">SMS Service</a>
                <a href="{{ route('mystore.invoices') }}">Invoices</a>
                <a href="{{ route('mystore.payments') }}">Payments</a>
            </div>
        </details>
        <a href="{{ route('cart.index') }}">Cart <b class="site-count">{{ count(session('domain_cart', [])) }}</b></a>
        <a href="{{ route('support') }}">Support Tickets</a>
        <a href="{{ route('wallet.index') }}">Wallet</a>
        <a href="{{ route('wallet.index') }}#add-balance">Add Balance</a>
    </nav>
</details>
<script>
(function () {
    // The Client Area popup is a <details> element and the account popup
    // (#accountMenu) is toggled separately, so they can overlap. Keep the
    // two mutually exclusive + close both on outside clicks.
    // The capture phase is required: the account button calls
    // event.stopPropagation(), which would swallow a bubble-phase
    // document listener — a capture listener always sees the click first.
    document.addEventListener('click', function (e) {
        var details = document.querySelector('.site-client-area');
        if (details && details.open && !details.contains(e.target)) {
            details.removeAttribute('open');
        }
    }, true);

    var details = document.querySelector('.site-client-area');
    if (details) {
        details.addEventListener('toggle', function () {
            var menu = document.getElementById('accountMenu');
            if (details.open && menu) {
                menu.classList.remove('open');
            }
        });
    }
})();
</script>
