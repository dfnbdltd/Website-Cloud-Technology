{{--
    Shared "Transfer your domain to us" experience.

    Used by two surfaces, each keeping its own chrome:
      • public  — resources/views/coming-soon.blade.php  (site navbar + footer)
      • client  — resources/views/store/coming-soon.blade.php (sidebar + topbar)

    $dtVariant = 'public' | 'client' changes only the layout shape (full-bleed
    hero vs. a rounded card inside the workspace).

    The flow is deliberately front-end only: nothing is posted anywhere. The
    domain is validated locally and the Authorization Code dialog simulates the
    registrar hand-off, so the page can be demoed before the backend exists.
--}}
@php($dtVariant = $dtVariant ?? 'public')

<section class="dt-hero" aria-labelledby="dtTitle">
    <div class="dt-art" aria-hidden="true">
        <span class="dt-ring dt-ring--one"></span>
        <span class="dt-ring dt-ring--two"></span>
        <span class="dt-chip dt-chip--domain"><i class="dt-chip-dot"></i>example.com</span>
        <span class="dt-chip dt-chip--globe">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.5 4 5.7 4 9s-1.4 6.5-4 9c-2.6-2.5-4-5.7-4-9s1.4-6.5 4-9Z"/></svg>
        </span>
        <span class="dt-chip dt-chip--code">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            EPP code ready
        </span>
    </div>

    <div class="dt-hero-inner">
        <span class="dt-eyebrow">A better home for your domain</span>
        <h1 class="dt-title" id="dtTitle">Transfer your domain with confidence</h1>
        <p class="dt-lede">Bring your domain to CloudTech and keep your website, email and visitors moving without interruption.</p>

        <form class="dt-search" method="get" action="#" data-dt-form novalidate>
            <div class="dt-field">
                <span class="dt-search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16.2 16.2 4.3 4.3"/></svg>
                </span>
                <label class="dt-sr-only" for="dtDomain">Domain name you want to transfer</label>
                <input class="dt-input" id="dtDomain" name="domain" type="text" placeholder="example.com" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="dtError" data-dt-domain>
            </div>
            <button class="dt-submit" type="submit">
                Transfer now
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <div class="dt-error" id="dtError" role="alert" hidden data-dt-error>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5"/><circle cx="12" cy="16.6" r=".9" fill="currentColor" stroke="none"/><path d="M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg>
            <span data-dt-error-text>The domain you entered is not valid</span>
        </div>

        <div class="dt-result dt-result--not-found" role="status" hidden data-dt-not-found>
            <span class="dt-result-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="6.5"/>
                    <path d="m16 16 4.5 4.5M8.5 8.5l5 5m0-5-5 5"/>
                </svg>
            </span>
            <div class="dt-result-copy">
                <span class="dt-result-label">Domain not available</span>
                <h2>We couldn&rsquo;t find <strong data-dt-not-found-domain>that domain</strong></h2>
                <p>Check the spelling or try another domain. If you already own it, make sure your current registrar has not placed it on hold.</p>
            </div>
            <button class="dt-result-retry" type="button" data-dt-retry>Try another domain</button>
        </div>
    </div>
</section>

<section class="dt-footer-cta" aria-labelledby="dtFooterCtaTitle">
    <div class="dt-footer-cta-inner">
        <div>
            <span class="dt-kicker">Ready when you are</span>
            <h2 id="dtFooterCtaTitle">Move your domain to a better home.</h2>
            <p>Enter your domain above and we&rsquo;ll guide you through the next step. It only takes a moment to get started.</p>
        </div>
        <span class="dt-footer-cta-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 12h15M13 6l6 6-6 6"/>
            </svg>
        </span>
    </div>
</section>

<div class="dt-modal" id="dtModal" role="dialog" aria-modal="true" aria-labelledby="dtModalTitle" hidden data-dt-modal>
    <div class="dt-dialog" role="document">
        <button class="dt-modal-close" type="button" aria-label="Close dialog" data-dt-close>&times;</button>

        <div class="dt-dialog-pane" data-dt-form-state>
            <header class="dt-dialog-head">
                <h2 id="dtModalTitle">Authorization Code</h2>
            </header>

            <div class="dt-dialog-body">
                <p class="dt-dialog-copy">To initiate a transfer you will need to obtain the authorization code from your current registrar. These can often be referred to as either the epp code or auth code. They act as a password and are unique to the domain name.</p>

                <p class="dt-dialog-domain">Transferring <strong data-dt-modal-domain>example.com</strong></p>

                <label class="dt-label" for="dtAuthCode">Authorization Code</label>
                <input class="dt-auth-input" id="dtAuthCode" type="text" placeholder="Epp Code / Auth Code" autocomplete="off" autocapitalize="none" spellcheck="false" data-dt-auth>

                <p class="dt-auth-error" role="alert" hidden data-dt-auth-error>Enter the authorization code from your current registrar.</p>

                <div class="dt-dialog-actions">
                    <button class="dt-btn dt-btn--primary" type="button" data-dt-confirm>Confirm</button>
                    <button class="dt-btn dt-btn--ghost" type="button" data-dt-close>Cancel</button>
                </div>
            </div>
        </div>

        <div class="dt-dialog-pane dt-success" data-dt-success hidden>
            <span class="dt-success-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7"/></svg>
            </span>
            <h2 class="dt-success-title">Transfer request received</h2>
            <p class="dt-success-copy">We&rsquo;ve started the transfer for <strong data-dt-success-domain>example.com</strong>. Your current registrar will email you to approve it &mdash; most transfers finish within 5&ndash;7 days.</p>
            <button class="dt-btn dt-btn--primary" type="button" data-dt-done>Done</button>
        </div>
    </div>
</div>
