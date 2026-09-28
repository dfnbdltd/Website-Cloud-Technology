<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Fast hosting'])
</head>
<body>
    <div class="site-shell">
        <div class="topbar"><div class="container topbar-inner"><span>Fast, secure and reliable hosting for Bangladesh</span><span>24/7 support &nbsp;·&nbsp; +880 9613 003 001</span></div></div>
        <header class="navbar container">
            <a class="brand" href="{{ route('landing') }}"><span class="brand-mark">H</span><span>host<span class="brand-accent">nova</span></span></a>
            <nav class="nav-links"><a href="#hosting">Hosting</a><a href="#services">Services</a><a href="#why-us">Why HostNova</a><a href="#support">Support</a></nav>
            <div class="nav-actions"><a class="login-link" href="{{ route('login') }}">Client login</a><a class="button button-small" href="#plans">Get started <span>↗</span></a></div>
        </header>

        <main>
            <section class="hero container">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="pulse"></span> Bangladesh's trusted cloud partner</div>
                    <h1>Hosting that keeps<br><em>your business moving.</em></h1>
                    <p>Launch faster with powerful BDIX hosting, effortless management and a support team that is always in your corner.</p>
                    <div class="hero-actions"><a class="button" href="#plans">Explore hosting plans <span>↗</span></a><a class="text-link" href="#why-us">See why customers stay <span>→</span></a></div>
                    <div class="hero-proof"><div class="avatars"><span>MR</span><span>SA</span><span>IH</span><span>+</span></div><div><strong>7,000+ happy customers</strong><small>Across 45+ countries</small></div></div>
                </div>
                <div class="hero-visual"><div class="glow"></div><div class="server-card card-main"><div class="server-top"><span class="status-dot"></span><strong>BDIX Cloud cluster</strong><span class="more">•••</span></div><div class="server-icon">⌁</div><div class="server-metric"><strong>99.99%</strong><span>uptime this month</span></div><div class="metric-line"><i></i></div><div class="server-footer"><span>Latency</span><strong>12 ms</strong><span>Secure</span><strong>SSL</strong></div></div><div class="floating-card float-speed"><span class="mini-icon">↯</span><div><strong>200x</strong><small>faster BDIX</small></div></div><div class="floating-card float-shield"><span class="mini-icon">✓</span><div><strong>Protected</strong><small>24/7 monitoring</small></div></div><div class="visual-grid"></div></div>
            </section>

            <section class="trust-strip"><div class="container trust-inner"><span>Built for teams who care about</span><strong>Speed</strong><strong>Security</strong><strong>Scale</strong><strong>Support</strong></div></section>

            <section class="section container" id="plans"><div class="section-heading"><div><div class="eyebrow">Simple, transparent pricing</div><h2>Everything you need to<br><em>grow online.</em></h2></div><p>From your first website to your next big product, choose the plan that fits your ambition.</p></div><div class="plan-grid"><article class="plan-card"><div class="plan-label">STARTER</div><h3>BDIX Budget</h3><p>For personal sites and new ideas.</p><div class="price"><sup>৳</sup> 69 <small>/month</small></div><a href="{{ route('login') }}" class="plan-button">View plan <span>↗</span></a><ul><li>5 GB NVMe storage</li><li>Free SSL certificate</li><li>Daily backups</li></ul></article><article class="plan-card featured"><div class="popular">Most popular</div><div class="plan-label">BUSINESS</div><h3>BDIX Premium</h3><p>For growing businesses and teams.</p><div class="price"><sup>৳</sup> 199 <small>/month</small></div><a href="{{ route('login') }}" class="plan-button">Choose premium <span>↗</span></a><ul><li>50 GB NVMe storage</li><li>Unlimited websites</li><li>Priority 24/7 support</li></ul></article><article class="plan-card"><div class="plan-label">ADVANCED</div><h3>Cloud VPS</h3><p>Dedicated power for high traffic.</p><div class="price"><sup>৳</sup> 1,722 <small>/month</small></div><a href="{{ route('login') }}" class="plan-button">View plan <span>↗</span></a><ul><li>4 vCPU · 8 GB RAM</li><li>NVMe SSD storage</li><li>Root access included</li></ul></article></div></section>

            <section class="feature-band" id="why-us"><div class="container feature-layout"><div><div class="eyebrow">Hosting without the headaches</div><h2>More than a server.<br><em>A team behind you.</em></h2><p>We handle the complicated bits so you can focus on your customers. Every account comes with the tools, speed and human support to help you go further.</p><a class="text-link light-link" href="#support">Meet our support team <span>→</span></a></div><div class="feature-list"><div><span>01</span><strong>Free website migration</strong><p>Move your site to us and let our experts do the heavy lifting.</p></div><div><span>02</span><strong>99.9% uptime guarantee</strong><p>Your website stays available when your customers need it.</p></div><div><span>03</span><strong>Real human support</strong><p>Fast answers from people who understand your business.</p></div></div></div></section>
            <section class="cta-section container" id="support"><div><div class="eyebrow">Ready when you are</div><h2>Put your website<br><em>in good hands.</em></h2></div><a class="button button-light" href="{{ route('login') }}">Open client area <span>↗</span></a></section>
        </main>
        <footer><div class="container footer-inner"><a class="brand" href="{{ route('landing') }}"><span class="brand-mark">H</span><span>host<span class="brand-accent">nova</span></span></a><span>© {{ date('Y') }} HostNova. Built for the web.</span><div><a href="#">Privacy</a><a href="#">Terms</a></div></div></footer>
    </div>
</body>
