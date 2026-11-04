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
                <input class="dt-input" id="dtDomain" name="domain" type="text" value="{{ old('domain') }}" placeholder="Insert Your Domain Here" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="dtError" data-dt-domain>
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

                <p class="dt-dialog-domain">Transferring <strong data-dt-modal-domain>Insert Your Domain Here</strong></p>
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
        <div class="dt-guide-heading">
            <span class="dt-kicker">Before you begin</span>
            <h2 id="dtGuideTitle">Prepare your domain for transfer</h2>
            <p>Have these four details ready and your transfer will be quick and straightforward.</p>
        </div>
        <ul class="dt-checklist dt-prep-grid">
            <li><span class="dt-guide-icon">01</span><div><strong>Unlock the domain.</strong><p>Turn off the transfer lock at your current registrar.</p></div></li>
            <li><span class="dt-guide-icon">02</span><div><strong>Check the 60-day rule.</strong><p>A domain generally cannot move within 60 days of registration or a previous transfer.</p></div></li>
            <li><span class="dt-guide-icon">03</span><div><strong>Get your EPP/Auth code.</strong><p>Request the code from your current registrar and keep it private.</p></div></li>
            <li><span class="dt-guide-icon">04</span><div><strong>Check your admin email.</strong><p>Make sure you can approve the transfer confirmation email.</p></div></li>
        </ul>

        <div class="dt-steps-heading">
            <span class="dt-kicker">Simple process</span>
            <h2>Transfer steps</h2>
        </div>
        <ol class="dt-transfer-steps">
            <li><span>01</span><div><strong>Start your request</strong><p>Sign in to your Cloud Technology account or continue as a guest.</p></div></li>
            <li><span>02</span><div><strong>Enter your details</strong><p>Provide the domain and EPP/Auth code, then continue to checkout.</p></div></li>
            <li><span>03</span><div><strong>Review and pay</strong><p>Review the live transfer price and complete payment securely.</p></div></li>
            <li><span>04</span><div><strong>Approve the transfer</strong><p>Confirm the email sent to your administrative contact.</p></div></li>
        </ol>

        <div class="dt-after-card">
            <div class="dt-after-card-icon" aria-hidden="true">✓</div>
            <div><span class="dt-kicker">After submitting</span><strong>Keep the domain unlocked while the transfer is processing.</strong><p>Transfers typically take 5&ndash;7 days. If anything remains pending beyond that, contact support with your order reference.</p></div>
        </div>
    </div>
</section>
