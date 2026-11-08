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
                Continue transfer
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
            </button>
        </form>

        <div class="dt-error" id="dtError" role="alert" @if(!$errors->any()) hidden @endif data-dt-error>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5"/><circle cx="12" cy="16.6" r=".9" fill="currentColor" stroke="none"/><path d="M10.3 3.8 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg>
            <span data-dt-error-text>{{ $errors->first() ?: 'The domain you entered is not valid' }}</span>
        </div>

    </div>
</section>

<section class="dt-guide" aria-labelledby="dtGuideTitle">
    <div class="dt-guide-inner">
        <div class="dt-guide-heading">
            <span class="dt-kicker">Everything you need to know</span>
            <h2 id="dtGuideTitle">Domain Transfer Procedure</h2>
            <p>You can transfer your domain to Cloud Technology by submitting request your domain name and EPP/transfer code through our Cloud Technology Domain Transfer Channel.</p>
        </div>

        <div class="dt-procedure-section">
            <div class="dt-procedure-heading">
                <span class="dt-procedure-icon dt-procedure-icon--requirements" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 19 6v5c0 4.5-2.8 8.3-7 10-4.2-1.7-7-5.5-7-10V6l7-3Z"></path>
                        <path d="m8.7 12 2.1 2.1 4.5-4.6"></path>
                    </svg>
                </span>
                <h3>Pre-requirements to Transfer a Domain to Cloud Technology</h3>
            </div>
            <ul class="dt-checklist dt-prep-grid">
                <li>
                    <span class="dt-guide-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg></span>
                    <div><strong>Domain Lock Status</strong><p>Make sure the domain is unlocked at your current registrar before starting transfer the domain process. A WHOIS lookup should not show “clientTransferProhibited”.</p></div>
                </li>
                <li>
                    <span class="dt-guide-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 9h16"></path><path d="M8 13h3M8 16h5"></path></svg></span>
                    <div><strong>60-Day Rule</strong><p>Confirm domain transfer is not within a 60-day transfer restriction caused by recent registration, transfer or registrant-detail changes. The domain cannot be transferred if it was registered or previously transferred within the last 60 days.</p></div>
                </li>
                <li>
                    <span class="dt-guide-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"></circle><path d="m11 12 7-7 3 3-7 7M16 7l2 2"></path></svg></span>
                    <div><strong>Transfer Auth/EPP Code</strong><p>You need the Auth/EPP code (transfer authorization code, which will work as password) from your current registrar.</p></div>
                </li>
                <li>
                    <span class="dt-guide-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg></span>
                    <div><strong>Admin Email</strong><p>Make sure the administrative contact email address at your current registrar is active and accessible to approve the transfer request.</p></div>
                </li>
            </ul>
        </div>

        <div class="dt-procedure-section dt-procedure-section--steps">
            <div class="dt-procedure-heading">
                <span class="dt-procedure-icon dt-procedure-icon--procedure" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 8h12"></path>
                        <path d="m12 4 4 4-4 4"></path>
                        <path d="M20 16H8"></path>
                        <path d="m12 12-4 4 4 4"></path>
                    </svg>
                </span>
                <div class="dt-procedure-heading-copy">
                    <h3>Domain Transfer Procedure</h3>
                </div>
                <span class="dt-steps-count">7 steps</span>
            </div>

            <div class="dt-transfer-panel">
                <ol class="dt-transfer-steps">
                    <li class="is-active" aria-current="step">
                        <button type="button" aria-label="Select step 1"><span>01</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Sign In to your Cloud Technology Account.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 2"><span>02</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Go to &ldquo;Domain &rarr; Domain Transfer&rdquo;.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 3"><span>03</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Enter the domain name and enter the Auth/EPP code, then click continue.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 4"><span>04</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Click on &ldquo;Place Order&rdquo; and complete the payment.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 5"><span>05</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">The transfer begins after payment is completed.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 6"><span>06</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Approve the confirmation email sent to your administrative contact address.</p>
                        </div>
                    </li>
                    <li>
                        <button type="button" aria-label="Select step 7"><span>07</span></button>
                        <div class="dt-step-body">
                            <p class="dt-step-text">Update your DNS or name servers once the transfer finishes if you are also moving your hosting.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>

        <div class="dt-procedure-section dt-procedure-section--after">
            <div class="dt-procedure-heading">
                <span class="dt-procedure-icon dt-procedure-icon--complete" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="8.5"></circle>
                        <path d="m8.2 12.2 2.5 2.5 5.2-5.4"></path>
                    </svg>
                </span>
                <h3>After Submitting Transfer Procedure</h3>
            </div>
            <div class="dt-after-card">
                <ul class="dt-after-list">
                    <li>Keep the domain unlocked.</li>
                    <li>Do not change the EPP/authorization code while the transfer is in progress.</li>
                    <li>Transfers typically complete within 5-7 days. Some entensions may have different transfer and renewal rules.</li>
                    <li>If the transfer fails, or it remains pending beyond 7 days, keep the error details or a screenshot available for assistance.</li>
                    <li>Please try these steps and let me know if you have any questions or need further assistance. I’ll be happy to help you.</li>
                </ul>
            </div>
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
                <p class="dt-dialog-copy">We check transfer eligibility and domain lock status before checkout. The registry confirms the EPP code when the transfer is submitted after payment.</p>

                <div class="dt-dialog-actions">
                    <button class="dt-btn dt-btn--primary" type="submit" data-dt-confirm>Continue to checkout</button>
                    <button class="dt-btn dt-btn--ghost" type="button" data-dt-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
