@php($domainMenuActive = in_array($active, ['domains', 'domain-transfer']))
@php($hostingMenuActive = in_array($active, ['shared-hosting', 'hosting-shared', 'hosting-vps', 'hosting-dedicated']))
@php($walletActive = $active === 'balance')
@php($myStoreMenuActive = in_array($active, ['mystore', 'my-domains', 'hosting', 'ssl', 'email', 'sms', 'invoices', 'payments']))
<link rel="stylesheet" href="{{ asset('css/sidebar-nav.css') }}?v={{ filemtime(public_path('css/sidebar-nav.css')) }}">
<aside class="domain-sidebar" id="clientSidebar">
    <a class="domain-brand" href="{{ route('landing') }}" title="Go to home page" aria-label="Go to home page">@include('partials.logo', ['gid' => 's'])<span class="sidebar-brand-name">Cloud <em>Technology</em></span></a>
    <nav>
        <span class="sidebar-nav-label">Workspace</span>
        <a data-sidebar-label="Home" href="{{ route('landing') }}"><b>⌂</b><span>Home</span></a>
        <a data-sidebar-label="Dashboard" {!! $active === 'dashboard' ? 'class="active"' : '' !!} href="{{ route('dashboard') }}"><b>◈</b><span>Dashboard</span></a>
        <span class="sidebar-nav-label sidebar-nav-label-services">Services</span>
        <a data-sidebar-label="Domains" href="#" data-current="{{ $domainMenuActive ? 'true' : 'false' }}" {!! $domainMenuActive ? 'class="active open domain-registration-toggle service-group-start service-nav-item"' : 'class="domain-registration-toggle service-group-start service-nav-item"' !!} aria-expanded="{{ $domainMenuActive ? 'true' : 'false' }}" aria-controls="domainRegistrationSubnav"><b>◎</b><span>Domains</span><i class="nav-caret nav-toggle" aria-hidden="true"></i></a>
        <div class="subnav{{ $domainMenuActive ? ' open' : '' }}" id="domainRegistrationSubnav">
            <div class="subnav-inner">
                <a data-sidebar-label="Domain Registration" {!! $active === 'domains' ? 'class="active"' : '' !!} href="{{ route('domains.index') }}"><span>Domain Registration</span></a>
                <a data-sidebar-label="Domain Transfer" {!! $active === 'domain-transfer' ? 'class="active"' : '' !!} href="{{ route('client.coming-soon', 'domain-transfer') }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}"><span>Domain Transfer</span></a>
            </div>
        </div>
        <a data-sidebar-label="Web Hosting" href="#" data-current="{{ $hostingMenuActive ? 'true' : 'false' }}" {!! $hostingMenuActive ? 'class="active open hosting-package-toggle service-nav-item"' : 'class="hosting-package-toggle service-nav-item"' !!} aria-expanded="{{ $hostingMenuActive ? 'true' : 'false' }}" aria-controls="hostingPackageSubnav"><b>▤</b><span>Web Hosting</span><i class="nav-caret nav-toggle" aria-hidden="true"></i></a>
        <div class="subnav{{ $hostingMenuActive ? ' open' : '' }}" id="hostingPackageSubnav">
            <div class="subnav-inner">
                <a data-sidebar-label="Shared Hosting" {!! in_array($active, ['shared-hosting', 'hosting-shared']) ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'shared') }}"><span>Shared Hosting</span></a>
                <a data-sidebar-label="VPS Hosting" {!! $active === 'hosting-vps' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'vps') }}"><span>VPS Hosting</span></a>
                <a data-sidebar-label="Dedicated Hosting" {!! $active === 'hosting-dedicated' ? 'class="active"' : '' !!} href="{{ route('client.hosting', 'dedicated') }}"><span>Dedicated Hosting</span></a>
            </div>
        </div>
        <a data-sidebar-label="SSL Certificate" {!! $active === 'ssl-certificate' ? 'class="active service-nav-item"' : 'class="service-nav-item"' !!} href="{{ route('client.coming-soon', 'ssl-certificate') }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}"><b>◇</b><span>SSL Certificate</span></a>
        <a data-sidebar-label="Business Email" {!! $active === 'business-email' ? 'class="active service-nav-item"' : 'class="service-nav-item"' !!} href="{{ route('client.coming-soon', 'business-email') }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}"><b>✉</b><span>Business Email</span></a>
        <a data-sidebar-label="SMS Service" {!! $active === 'sms-service' ? 'class="active service-group-end service-nav-item"' : 'class="service-group-end service-nav-item"' !!} href="{{ route('client.coming-soon', 'sms-service') }}?back={{ urlencode(\App\Support\ReturnTo::path()) }}"><b>✆</b><span>SMS Service</span></a>
        <span class="sidebar-nav-label sidebar-nav-label-store">Account</span>
        <a data-sidebar-label="My Store" href="#" data-current="{{ $myStoreMenuActive ? 'true' : 'false' }}" {!! $myStoreMenuActive ? 'class="active open service-nav-item"' : 'class="service-nav-item"' !!} aria-expanded="{{ $myStoreMenuActive ? 'true' : 'false' }}" aria-controls="myStoreSubnav"><b>▦</b><span>My Store</span><i class="nav-caret nav-toggle" aria-hidden="true"></i></a>
        <div class="subnav{{ $myStoreMenuActive ? ' open' : '' }}" id="myStoreSubnav">
            <div class="subnav-inner">
                {{-- All seven sections are always listed, whether they hold anything or not. --}}
                <a data-sidebar-label="All Items" {!! $active === 'mystore' ? 'class="active"' : '' !!} href="{{ route('mystore') }}"><span>All Items</span></a>
                <a data-sidebar-label="Domain" {!! $active === 'my-domains' ? 'class="active"' : '' !!} href="{{ route('mystore.domains') }}"><span>Domain</span></a>
                <a data-sidebar-label="Web Hosting" {!! $active === 'hosting' ? 'class="active"' : '' !!} href="{{ route('mystore.hosting') }}"><span>Web Hosting</span></a>
                <a data-sidebar-label="SSL Certificate" {!! $active === 'ssl' ? 'class="active"' : '' !!} href="{{ route('mystore.service', 'ssl') }}"><span>SSL Certificate</span></a>
                <a data-sidebar-label="Business Email" {!! $active === 'email' ? 'class="active"' : '' !!} href="{{ route('mystore.service', 'email') }}"><span>Business Email</span></a>
                <a data-sidebar-label="SMS Service" {!! $active === 'sms' ? 'class="active"' : '' !!} href="{{ route('mystore.service', 'sms') }}"><span>SMS Service</span></a>
                <a data-sidebar-label="Invoices" {!! $active === 'invoices' ? 'class="active"' : '' !!} href="{{ route('mystore.invoices') }}"><span>Invoices</span></a>
                <a data-sidebar-label="Payments" {!! $active === 'payments' ? 'class="active"' : '' !!} href="{{ route('mystore.payments') }}"><span>Payments</span></a>
            </div>
        </div>
        <a data-sidebar-label="Cart" {!! $active === 'cart' ? 'class="active"' : '' !!} href="{{ route('cart.index') }}"><b>🛒</b><span>Cart</span> @if(count(session('domain_cart', [])))<i class="nav-count">{{ count(session('domain_cart', [])) }}</i>@endif</a>
        <a data-sidebar-label="Support Tickets" {!! $active === 'support' ? 'class="active"' : '' !!} href="{{ route('support') }}"><b>↪</b><span>Support Tickets</span></a>
        <a data-sidebar-label="Wallet" {!! $walletActive ? 'class="active"' : '' !!} href="{{ route('wallet.index') }}"><b>৳</b><span>Wallet</span> @if(session('customer'))<i class="nav-count nav-balance">৳{{ number_format(session('customer.balance', 0)) }}</i>@endif</a>
        <a data-sidebar-label="Add Balance" {!! $active === 'add-balance' ? 'class="active"' : '' !!} href="{{ route('wallet.index') }}#add-balance"><b>＋</b><span>Add Balance</span></a>
    </nav>
</aside>
<button class="sidebar-backdrop" type="button" aria-label="Close navigation menu" onclick="document.getElementById('clientSidebar').classList.remove('mobile-open');document.querySelector('.sidebar-toggle').setAttribute('aria-expanded','false')"></button>
@include('partials.coming-soon-modal')
<script>
(function () {
    var sidebar = document.getElementById('clientSidebar');
    if (!sidebar) { return; }

    /**
     * Keep the newly opened list visible while it grows.
     *
     * The old version waited 340ms for the open animation, then smooth-scrolled
     * — which read as "lag, then jump". Instead this follows the growing list on
     * every frame, so the scroll and the reveal finish together: no pause and
     * no second movement after the list already looks open.
     */
    var revealFrame = null;

    function cancelReveal() {
        if (revealFrame) {
            cancelAnimationFrame(revealFrame);
            revealFrame = null;
        }
    }

    function revealList(submenu) {
        var nav = sidebar.querySelector('nav');
        if (!nav) { return; }

        cancelReveal();

        // A little longer than the 240ms open animation, so the follow loop
        // cannot outlive the reveal even if a row is slow to lay out.
        var stopAt = performance.now() + 500;

        function follow(now) {
            var navRect = nav.getBoundingClientRect();
            var listRect = submenu.getBoundingClientRect();
            var hidden = listRect.bottom - navRect.bottom + 12;

            // Move in step with the row the animation just revealed.
            if (hidden > 0) { nav.scrollTop += hidden; }

            if (now < stopAt) {
                revealFrame = requestAnimationFrame(follow);
            } else {
                // Land exactly on the final row, then stop.
                if (hidden > 0) { nav.scrollTop += hidden; }
                revealFrame = null;
            }
        }

        revealFrame = requestAnimationFrame(follow);
    }

    /**
     * Open or close one group.
     *
     * The navy pill means exactly one thing: THIS GROUP IS OPEN. So `active`
     * is bound to `open` and nothing else — a group that is merely the page
     * you are on gets no fill while collapsed, otherwise opening Web Hosting
     * left My Store looking active too (two rows claiming to be open at once).
     * Location is carried by aria-current, which is invisible, and by the
     * highlighted child row inside the group once it is open.
     */
    function setGroup(trigger, open, reveal) {
        var submenu = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!submenu) { return; }

        if (!open) { cancelReveal(); }

        submenu.classList.toggle('open', open);
        trigger.classList.toggle('open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        trigger.classList.toggle('active', open);
        markCurrent(trigger);

        if (open && reveal) { revealList(submenu); }
    }

    /** "You are here" for assistive tech; it paints nothing. */
    function markCurrent(trigger) {
        if (trigger.getAttribute('data-current') === 'true') {
            trigger.setAttribute('aria-current', 'true');
        } else {
            trigger.removeAttribute('aria-current');
        }
    }

    /**
     * Open one group and close every other, in that order. The click handler
     * and the deep-link pass both go through here, so a programmatic open can
     * never leave two navy pills behind.
     */
    function openOnly(trigger, reveal) {
        openTriggers().forEach(function (other) {
            if (other !== trigger) { setGroup(other, false, false); }
        });
        setGroup(trigger, true, reveal);
    }

    function openTriggers() {
        return sidebar.querySelectorAll('nav > a[aria-controls][aria-expanded="true"]');
    }

    // Start from the state the server rendered, with the same rules the click
    // handler uses: navy = open, nothing at all = closed.
    [].slice.call(sidebar.querySelectorAll('nav > a[aria-controls]')).forEach(function (trigger) {
        setGroup(trigger, trigger.getAttribute('aria-expanded') === 'true', false);
    });

    // One delegated handler: works for any present or future group and keeps
    // the markup free of inline JS.
    sidebar.addEventListener('click', function (event) {
        var trigger = event.target.closest('nav > a[aria-controls]');
        if (!trigger || !sidebar.contains(trigger)) { return; }
        event.preventDefault();

        // Accordion: only one group stays open. Opening Web Hosting closes
        // Domains first, so the list never grows under the pointer — and the
        // closed group drops its navy pill in the same pass.
        if (trigger.getAttribute('aria-expanded') !== 'true') {
            openOnly(trigger, true);
            return;
        }

        setGroup(trigger, false, false);
    });

    /* ── Which section of this page you are looking at ────────────────────
       My Store sections are real pages now (/mystore/domains, ...), so the
       server marks the current row. The fragment pass below still covers
       older #section links (e.g. /mystore#payments): a fragment is never
       sent to the server, so the markup could only mark the page as a whole
       and "Domain" kept the highlight while the browser sat on #payments.
       The row for the section you land on takes the highlight and its
       siblings drop it; with no fragment the page's default section keeps it. */
    var sectionLinks = [].slice.call(sidebar.querySelectorAll('nav .subnav a[href]'));

    function sectionTarget(link) {
        try {
            return new URL(link.getAttribute('href'), window.location.href);
        } catch (error) {
            return null;
        }
    }

    // what the server decided, so we can fall back to it
    var defaultLinks = sectionLinks.filter(function (link) {
        return link.classList.contains('active');
    });

    function markSection(link, active) {
        link.classList.toggle('active', active);
        if (active) { link.setAttribute('aria-current', 'true'); } else { link.removeAttribute('aria-current'); }
    }

    function syncSectionHighlight() {
        var here = window.location.pathname;
        var hash = window.location.hash;
        var claimed = null;

        if (hash) {
            sectionLinks.forEach(function (link) {
                var url = sectionTarget(link);
                if (url && url.pathname === here && url.hash === hash) { claimed = link; }
            });
        }

        // No fragment (or one this page does not own): the server's default
        // section stays marked.
        if (!claimed) {
            sectionLinks.forEach(function (link) {
                markSection(link, defaultLinks.indexOf(link) !== -1);
            });
            return;
        }

        var submenu = claimed.closest('.subnav');
        sectionLinks.forEach(function (link) {
            if (link.closest('.subnav') === submenu) { markSection(link, link === claimed); }
        });

        // A fragment can deep-link into a collapsed group: open it.
        var group = submenu ? submenu.previousElementSibling : null;
        if (group && group.hasAttribute('aria-controls') && group.getAttribute('aria-expanded') !== 'true') {
            openOnly(group, false);
        }
    }

    syncSectionHighlight();
    window.addEventListener('hashchange', syncSectionHighlight);

    // Escape closes the open group when focus is inside the sidebar.
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || !sidebar.contains(document.activeElement)) { return; }
        openTriggers().forEach(function (trigger) {
            setGroup(trigger, false, false);
        });
    });
}());
</script>
