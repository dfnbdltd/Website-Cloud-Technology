{{--
    Shared "Transfer your domain to us" experience.

    Used by two surfaces, each keeping its own chrome:
      • public  — resources/views/coming-soon.blade.php  (site navbar + footer)
      • client  — resources/views/store/coming-soon.blade.php (sidebar + topbar)

    $dtVariant = 'public' | 'client' changes only the layout shape (full-bleed
    hero vs. a rounded card inside the workspace).

    Transfer requests use the shared domain checkout and ResellerClub integration.
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
            EPP code required
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
                <input class="dt-input" id="dtDomain" name="domain" type="text" value="{{ old('domain') }}" placeholder="example.com" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="dtError" data-dt-domain>
            </div>
            <button class="dt-submit" type="submit">
                Transfer now
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <div class="dt-error" id="dtError" role="alert" @if(!$errors->any()) hidden @endif data-dt-error>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5"/><circle cx="12" cy="16.6" r=".9" fill="currentColor" stroke="none"/><path d="M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg>
            <span data-dt-error-text>{{ $errors->first() ?: 'The domain you entered is not valid' }}</span>
        </div>

    </div>
</section>

<section class="dt-footer-cta" aria-labelledby="dtFooterCtaTitle">
    <div class="dt-footer-cta-inner">
        <div>
            <span class="dt-kicker">Ready when you are</span>
            <h2 id="dtFooterCtaTitle">Move your domain to a better home.</h2>
            <p>Check the transfer requirements below, then enter your domain and authorization code to see the live transfer price.</p>
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

            <form class="dt-dialog-body" method="POST" action="{{ route('domains.transfer') }}" data-dt-transfer-form>
                @csrf
                <p class="dt-dialog-copy">Get the authorization code from your current registrar. It may also be called an EPP or Auth code; treat it like a password.</p>

                <p class="dt-dialog-domain">Transferring <strong data-dt-modal-domain>example.com</strong></p>
                <input type="hidden" name="domain" value="" data-dt-transfer-domain>

                <label class="dt-label" for="dtAuthCode">Authorization Code</label>
                <input class="dt-auth-input" id="dtAuthCode" name="auth_code" type="password" placeholder="EPP / Auth code" autocomplete="off" autocapitalize="none" spellcheck="false" required data-dt-auth>

                <p class="dt-auth-error" role="alert" hidden data-dt-auth-error>Enter the authorization code from your current registrar.</p>

                <div class="dt-dialog-actions">
                    <button class="dt-btn dt-btn--primary" type="submit" data-dt-confirm>Continue to checkout</button>
                    <button class="dt-btn dt-btn--ghost" type="button" data-dt-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="dt-guide" aria-labelledby="dtGuideTitle">
    <div class="dt-guide-inner">
        <span class="dt-kicker">Before you begin</span>
        <h2 id="dtGuideTitle">Prepare your domain for transfer</h2>
        <ul class="dt-checklist">
            <li><strong>Unlock the domain.</strong> Turn off the transfer lock at your current registrar. A WHOIS lookup should not show <code>clientTransferProhibited</code>.</li>
            <li><strong>Check the 60-day rule.</strong> A domain generally cannot be transferred within 60 days of registration, a previous transfer, or a change to registrant details.</li>
            <li><strong>Get your EPP/Auth code.</strong> Request the transfer authorization code from your current registrar. Keep it private; it works like a password.</li>
            <li><strong>Check your admin email.</strong> Make sure you can receive and approve the transfer confirmation email sent to the registrant or administrative contact.</li>
        </ul>

        <h2>Transfer steps</h2>
        <ol class="dt-transfer-steps">
            <li>Sign in to your Cloud Technology account. Guest checkout is also available.</li>
            <li>Unlock the domain at your current registrar and obtain its EPP/Auth code.</li>
            <li>Open <strong>Domain &rarr; Domain Transfer</strong>, enter the domain and code, and continue to checkout.</li>
            <li>Review the live transfer price, place the order, and complete payment.</li>
            <li>After payment, Cloud Technology submits the transfer request to the registrar.</li>
            <li>Approve the confirmation email sent to your administrative contact.</li>
            <li>When the transfer finishes, update DNS or nameservers if you are also moving your hosting.</li>
        </ol>

        <h2>After submitting</h2>
        <ul class="dt-checklist">
            <li>Keep the domain unlocked and do not change its EPP/Auth code while the transfer is in progress.</li>
            <li>Transfers typically take 5&ndash;7 days; some extensions have different transfer and renewal rules.</li>
            <li>If a transfer fails or remains pending beyond 7 days, keep the error details or a screenshot and contact support.</li>
        </ul>
        <p class="dt-guide-note">Questions or need help? Contact Cloud Technology support and include your order reference.</p>
    </div>
</section>
