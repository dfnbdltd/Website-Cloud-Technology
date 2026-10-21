<!doctype html>
<html lang="en">
<head>
@include('partials.head', ['title' => 'Create account'])
<style>
    /* Register v4 — single centred card, no split pane. Scoped to .reg4; tokens come from public/css/app.css. */
    .reg4-body{background:linear-gradient(160deg,#f6fbff 0%,#eef5ff 48%,#f7fbff 100%)}
    .reg4-bg{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;background:linear-gradient(160deg,#f6fbff 0%,#eef5ff 48%,#f7fbff 100%)}
    .reg4-bg:before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(36,117,232,.055) 1px,transparent 1px),linear-gradient(90deg,rgba(36,117,232,.055) 1px,transparent 1px);background-size:34px 34px}
    .reg4-bg i{position:absolute;border-radius:50%;filter:blur(72px)}
    .reg4-bg i:nth-child(1){width:440px;height:440px;background:rgba(36,117,232,.13);top:-160px;left:-140px}
    .reg4-bg i:nth-child(2){width:360px;height:360px;background:rgba(88,169,255,.11);bottom:-150px;right:-120px}
    .reg4-bg i:nth-child(3){width:220px;height:220px;background:rgba(54,184,134,.08);top:54%;left:63%}

    .reg4-wrap{position:relative;z-index:1;max-width:1060px;margin:auto;padding:48px 28px 64px}
    .reg4-brand{justify-content:center;margin-bottom:28px}
    .reg4-card{position:relative;background:#fff;border:1px solid rgba(226,234,242,.9);border-radius:22px;
        box-shadow:0 30px 80px -20px rgba(16,80,160,.18),0 10px 30px -12px rgba(16,44,79,.08);
        overflow:hidden;animation:reg4In .45s ease both}
    .reg4-card:before{content:'';position:absolute;top:0;left:0;right:0;height:4px;z-index:1;
        background:linear-gradient(90deg,var(--blue),#58a9ff 55%,var(--green))}
    @keyframes reg4In{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}

    /* Header */
    .reg4-head{padding:42px 50px 28px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff,#fbfdff)}
    .reg4-eyebrow{display:inline-flex;align-items:center;gap:9px;padding:8px 15px;border-radius:999px;background:var(--sky);font-size:11.5px}
    .reg4-eyebrow i{width:8px;height:8px;border-radius:50%;background:#3ac18a;box-shadow:0 0 0 4px rgba(58,193,138,.18)}
    .reg4-head h1{font:800 42px/1.08 'Manrope',sans-serif;letter-spacing:-1.8px;margin:18px 0 8px;color:var(--ink)}
    .reg4-head h1 span{color:var(--blue)}
    .reg4-head p{color:var(--muted);font-size:16px;line-height:1.6;margin:0;max-width:640px}

    /* Alerts */
    .reg4-alert{display:flex;align-items:flex-start;gap:12px;margin:20px 50px 0;padding:14px 16px;border-radius:11px;
        font-size:14px;line-height:1.55}
    .reg4-alert svg{flex:none;width:19px;height:19px;margin-top:2px}
    .reg4-alert strong{display:block;font-size:11.5px;letter-spacing:.5px;text-transform:uppercase;opacity:.8;margin-bottom:2px}
    .reg4-alert-error{background:#fff0ee;border:1px solid #ffd9d3;color:#b64a41}
    .reg4-alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#0b7a56}

    /* Form shell + sections */
    .reg4-form{padding:30px 50px 40px}
    .reg4-form fieldset{min-width:0;border:1px solid #e6edf5;border-radius:16px;padding:8px 26px 10px;margin:0 0 24px;background:#fff;transition:border-color .18s ease,box-shadow .18s ease}
    .reg4-form fieldset:focus-within{border-color:#c9def6;box-shadow:0 0 0 3px rgba(36,117,232,.06)}
    .reg4-form legend{display:flex;align-items:center;gap:10px;padding:0 9px;color:var(--ink);
        font:800 13.5px 'Manrope',sans-serif;letter-spacing:.8px;text-transform:uppercase}
    .reg4-step{display:grid;place-items:center;width:26px;height:26px;border-radius:8px;background:var(--sky);
        color:var(--blue);font:800 12px/1 'Manrope',sans-serif;letter-spacing:0}
    .reg4-note{color:var(--muted);font-size:13px;line-height:1.55;margin:9px 0 15px}

    .reg4-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 26px;margin-top:15px}
    .reg4-span{grid-column:1 / -1}

    /* Fields */
    .reg4-field{display:block;margin:0 0 18px;color:#33475e;font-size:14.5px;font-weight:700;letter-spacing:.2px}
    .reg4-field>span{display:block;margin-bottom:9px}
    .reg4-req{color:#e0574a;margin-left:2px}
    .reg4-opt{color:#8fa2b6;font-weight:500;font-size:12.5px;margin-left:4px}
    .reg4-field small.reg4-hint{display:block;margin-top:7px;color:#8fa2b6;font-size:12.5px;font-weight:500;line-height:1.5}

    .reg4-form input[type=text],.reg4-form input[type=email],.reg4-form input[type=tel],
    .reg4-form input[type=password],.reg4-form select{width:100%;display:block;border:1px solid #dbe6f0;border-radius:11px;
        padding:14px 16px;outline:none;font:16.5px 'DM Sans',sans-serif;background:#fbfdff;color:var(--ink);
        transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
    .reg4-form input:focus,.reg4-form select:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(36,117,232,.12);background:#fff}
    .reg4-form input::placeholder{color:#a9b8c8}
    .reg4-form select{appearance:none;-webkit-appearance:none;cursor:pointer;padding-right:42px;
        background-image:url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236f8197' d='M1.41.59L6 5.17 10.59.59 12 2 6 8 0 2z'/%3E%3C/svg%3E");
        background-repeat:no-repeat;background-position:right 16px center}

    /* Phone (dial code + number) */
    .reg4-phone{display:flex;gap:10px}
    .reg4-phone select{flex:0 0 112px}
    .reg4-phone input{flex:1;min-width:0}

    /* Password fields */
    .reg4-pw{position:relative}
    .reg4-pw input{padding-right:52px}
    .reg4-eye{position:absolute;top:9px;right:8px;display:grid;place-items:center;width:34px;height:34px;padding:0;
        border:0;border-radius:9px;background:transparent;color:#8fa2b6;cursor:pointer;
        transition:color .15s ease,background .15s ease}
    .reg4-eye svg{width:19px;height:19px}
    .reg4-eye svg[hidden]{display:none}
    .reg4-eye:hover{color:var(--blue);background:#f2f7ff}
    .reg4-eye:focus-visible{outline:2px solid var(--blue);outline-offset:1px}
    .reg4-pw .password-strength{margin:11px 0 0}
    .reg4-pw .password-match-status{margin-top:7px}

    /* Currency (single supported option) */
    .reg4-currency{display:flex;align-items:center;gap:10px;margin:0;padding:15px 16px;border:1px dashed #d4e1ef;
        border-radius:11px;background:#f6f9fd;color:#5f738a;font-size:13.5px;line-height:1.5;min-height:56px}
    .reg4-currency b{color:var(--ink);font-size:14px}

    /* Checkboxes — input first, label text after */
    .reg4-checks{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:4px 0 24px}
    .reg4-check{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid var(--line);border-radius:11px;
        background:#fbfdff;cursor:pointer;color:#5f738a;font-size:14px;font-weight:500;line-height:1.55;
        transition:border-color .15s ease,background .15s ease}
    .reg4-check:hover{border-color:#c9def6;background:#fff}
    .reg4-check input{flex:none;width:18px;height:18px;margin:2px 0 0;accent-color:var(--blue);cursor:pointer}
    .reg4-check:has(input:checked){border-color:#bcd8fb;background:#f4f9ff}
    .reg4-check strong{color:var(--ink);font-weight:700}
    .reg4-check a{color:var(--blue);font-weight:700}

    /* Submit + footer */
    .reg4-submit{width:100%;padding:17px 22px;border:0;border-radius:13px;cursor:pointer;display:inline-flex;
        align-items:center;justify-content:center;gap:11px;color:#fff;font:700 17px 'DM Sans',sans-serif;
        background:linear-gradient(180deg,#3b83f7,var(--blue));box-shadow:0 14px 28px rgba(36,117,232,.24);
        transition:transform .15s ease,box-shadow .15s ease}
    .reg4-submit:hover{transform:translateY(-1px);box-shadow:0 18px 34px rgba(36,117,232,.3)}
    .reg4-submit:active{transform:none}
    .reg4-submit:focus-visible{outline:2px solid #0f4a8a;outline-offset:2px}

    .reg4-signin{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:20px 0 0;
        padding:15px 17px;border:1px solid var(--line);border-radius:11px;background:#f6f9fd;color:#5f738a;font-size:14px}
    .reg4-signin a{color:var(--blue);font-weight:700}
    .reg4-back{display:block;text-align:center;margin-top:22px;color:#6c8197;font-size:14px;font-weight:600}
    .reg4-back:hover{color:var(--blue)}

    /* Responsive */
    @media(max-width:900px){
        .reg4-wrap{max-width:760px}
        .reg4-grid{grid-template-columns:1fr}
        .reg4-span{grid-column:auto}
        .reg4-checks{grid-template-columns:1fr}
    }
    @media(max-width:700px){
        .reg4-wrap{padding:26px 14px 44px}
        .reg4-brand{margin-bottom:18px}
        .reg4-card{border-radius:16px}
        .reg4-head{padding:28px 24px 20px}
        .reg4-head h1{font-size:32px;letter-spacing:-1.2px}
        .reg4-head p{font-size:14.5px}
        .reg4-form{padding:24px 22px 28px}
        .reg4-alert{margin:16px 22px 0;font-size:13px}
        .reg4-form fieldset{padding:6px 18px 8px;margin-bottom:18px}
        .reg4-form legend{font-size:12.5px}
        .reg4-field{font-size:14px;margin-bottom:16px}
        .reg4-form input[type=text],.reg4-form input[type=email],.reg4-form input[type=tel],
        .reg4-form input[type=password],.reg4-form select{font-size:16px;padding:13px 14px}
        .reg4-check{font-size:13.5px}
        .reg4-submit{font-size:16px;padding:16px 20px}
        .reg4-signin{flex-direction:column;align-items:flex-start;gap:6px;font-size:13.5px}
        .reg4-back{font-size:13.5px}
    }
    @media(max-width:380px){.reg4-phone select{flex-basis:88px}}
    @media(prefers-reduced-motion:reduce){.reg4-card{animation:none}}
</style>
</head>
<body class="auth-page reg4-body">
<div class="reg4-bg" aria-hidden="true"><i></i><i></i><i></i></div>

<div class="reg4-wrap">
    <a class="brand reg4-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'r'])</a>

    <main class="reg4-card">
        <header class="reg4-head">
            <div class="eyebrow reg4-eyebrow"><i aria-hidden="true"></i>Create customer account</div>
            <h1>Register<span>.</span></h1>
            <p>One account for your domains, hosting, invoices and support &mdash; it takes about two minutes.</p>
        </header>

        @if(session('success'))
            <div class="reg4-alert reg4-alert-success" role="status">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="reg4-alert reg4-alert-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                <span><strong>Please check the form</strong>{{ $errors->first() }}</span>
            </div>
        @endif

        <form class="reg4-form" method="POST" action="{{ route('register.submit') }}" data-password-strength>
            @csrf

            {{-- 1. Personal information --}}
            <fieldset>
                <legend><span class="reg4-step">1</span> Personal information</legend>
                <div class="reg4-grid">
                    <label class="reg4-field reg4-span">
                        <span>Full Name <em class="reg4-req">*</em></span>
                        <input type="text" name="full_name" value="{{ old('full_name', trim(old('first_name') . ' ' . old('last_name'))) }}"
                            placeholder="e.g. Md. Rahim Uddin" autocomplete="name" required autofocus>
                    </label>
                    <label class="reg4-field">
                        <span>Email Address <em class="reg4-req">*</em></span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" autocomplete="email" required>
                        <small class="reg4-hint">This becomes your login ID &mdash; we'll send a verification link here.</small>
                    </label>
                    <label class="reg4-field">
                        <span>Phone Number <em class="reg4-req">*</em></span>
                        <div class="reg4-phone">
                            <select name="phone_dial" aria-label="Country dial code">
                                <option value="+880" {{ old('phone_dial', '+880') === '+880' ? 'selected' : '' }}>+880</option>
                                <option value="+91" {{ old('phone_dial') === '+91' ? 'selected' : '' }}>+91</option>
                                <option value="+44" {{ old('phone_dial') === '+44' ? 'selected' : '' }}>+44</option>
                                <option value="+1" {{ old('phone_dial') === '+1' ? 'selected' : '' }}>+1</option>
                            </select>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="1XXXXXXXXX" inputmode="tel" autocomplete="tel-national" required>
                        </div>
                    </label>
                </div>
            </fieldset>

            {{-- 2. Billing address --}}
            <fieldset>
                <legend><span class="reg4-step">2</span> Billing address</legend>
                <p class="reg4-note">These details appear on your invoices &mdash; keep them accurate.</p>
                <div class="reg4-grid">
                    <label class="reg4-field">
                        <span>Company Name <small class="reg4-opt">(Optional)</small></span>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Company name" autocomplete="organization">
                    </label>
                    <label class="reg4-field">
                        <span>Country <em class="reg4-req">*</em></span>
                        <select name="country" autocomplete="country-name" required>
                            <option value="">Choose country</option>
                            <option value="Bangladesh" {{ old('country', 'Bangladesh') === 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                            <option value="India" {{ old('country') === 'India' ? 'selected' : '' }}>India</option>
                            <option value="Pakistan" {{ old('country') === 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
                            <option value="United States" {{ old('country') === 'United States' ? 'selected' : '' }}>United States</option>
                            <option value="United Kingdom" {{ old('country') === 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                            <option value="Other" {{ old('country') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </label>
                    <label class="reg4-field reg4-span">
                        <span>Street Address <em class="reg4-req">*</em></span>
                        <input type="text" name="address1" value="{{ old('address1') }}" placeholder="House, road, area" autocomplete="address-line1" required>
                    </label>
                    <label class="reg4-field">
                        <span>City <em class="reg4-req">*</em></span>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="City" autocomplete="address-level2" required>
                    </label>
                    <label class="reg4-field">
                        <span>Postcode <em class="reg4-req">*</em></span>
                        <input type="text" name="postcode" value="{{ old('postcode') }}" placeholder="Postcode" autocomplete="postal-code" required>
                    </label>
                </div>
            </fieldset>

            {{-- 3. Account security --}}
            <fieldset>
                <legend><span class="reg4-step">3</span> Account security</legend>
                <div class="reg4-grid">
                    <label class="reg4-field">
                        <span>Password <em class="reg4-req">*</em></span>
                        <div class="reg4-pw">
                            <input type="password" name="password" id="regPass" placeholder="Create a strong password" autocomplete="new-password" required>
                            <button type="button" class="reg4-eye" data-toggle-for="regPass" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                            </button>
                        </div>
                    </label>
                    <label class="reg4-field">
                        <span>Confirm Password <em class="reg4-req">*</em></span>
                        <div class="reg4-pw">
                            <input type="password" name="password_confirmation" id="regConfirm" placeholder="Repeat your password" autocomplete="new-password" required>
                            <button type="button" class="reg4-eye" data-toggle-for="regConfirm" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                            </button>
                        </div>
                    </label>
                    <label class="reg4-field">
                        <span>Security Question <em class="reg4-req">*</em></span>
                        <select name="security_question" required>
                            <option value="">Please choose one</option>
                            <option value="What is your mother's maiden name?" {{ old('security_question') === "What is your mother's maiden name?" ? 'selected' : '' }}>What is your mother's maiden name?</option>
                            <option value="What was the name of your first school?" {{ old('security_question') === 'What was the name of your first school?' ? 'selected' : '' }}>What was the name of your first school?</option>
                            <option value="What is your favourite sports team?" {{ old('security_question') === 'What is your favourite sports team?' ? 'selected' : '' }}>What is your favourite sports team?</option>
                            <option value="What was your first pet's name?" {{ old('security_question') === "What was your first pet's name?" ? 'selected' : '' }}>What was your first pet's name?</option>
                            <option value="What city were you born in?" {{ old('security_question') === 'What city were you born in?' ? 'selected' : '' }}>What city were you born in?</option>
                        </select>
                    </label>
                    <label class="reg4-field">
                        <span>Security Answer <em class="reg4-req">*</em></span>
                        <input type="text" name="security_answer" value="{{ old('security_answer') }}" placeholder="Your answer" required>
                        <small class="reg4-hint">Support may use this to verify your identity.</small>
                    </label>
                </div>
            </fieldset>

            {{-- 4. Preferences --}}
            <fieldset>
                <legend><span class="reg4-step">4</span> Preferences</legend>
                <div class="reg4-grid">
                    <label class="reg4-field">
                        <span>How did you find us? <small class="reg4-opt">(Optional)</small></span>
                        <select name="found_us">
                            <option value="">Choose one</option>
                            <option value="Google Search" {{ old('found_us') === 'Google Search' ? 'selected' : '' }}>Google Search</option>
                            <option value="Social Media" {{ old('found_us') === 'Social Media' ? 'selected' : '' }}>Social Media</option>
                            <option value="Friend or Colleague" {{ old('found_us') === 'Friend or Colleague' ? 'selected' : '' }}>Friend or Colleague</option>
                            <option value="Advertisement" {{ old('found_us') === 'Advertisement' ? 'selected' : '' }}>Advertisement</option>
                            <option value="Other" {{ old('found_us') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </label>
                    <div class="reg4-field">
                        <span>Billing Currency</span>
                        <div class="reg4-currency">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 7h6M9 10.5h6M12.5 7c2 0 2.5 1.2 2.5 3.5S14 14 12.5 14H9l5 4"/></svg>
                            <span><b>BDT &#2547;</b> &mdash; Bangladeshi Taka, the only supported currency.</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="currency" value="BDT">
            </fieldset>

            <div class="reg4-checks">
                <label class="reg4-check">
                    <input type="checkbox" name="newsletter" value="1" {{ old('newsletter') ? 'checked' : '' }}>
                    <span><strong>Join our mailing list</strong> &mdash; occasional news and offers by email. Unsubscribe any time.</span>
                </label>
                <label class="reg4-check">
                    <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                    <span>I have read and agree to the <a href="{{ route('under.construction', 'terms') }}">Terms of Service</a>.</span>
                </label>
            </div>

            <button class="reg4-submit" type="submit">Create account <span aria-hidden="true">&rarr;</span></button>

            <p class="reg4-signin">
                <span>Already registered? <a href="{{ route('login') }}">Sign in</a></span>
                <span><a href="{{ route('password.request') }}">Forgot password?</a></span>
            </p>
        </form>
    </main>

    <a class="reg4-back" href="{{ route('landing') }}">&larr; Back to homepage</a>
</div>

@include('partials.chat')
<script>
    (function () {
        var toggles = document.querySelectorAll('.reg4-eye');
        Array.prototype.forEach.call(toggles, function (toggle) {
            toggle.addEventListener('click', function () {
                var field = document.getElementById(toggle.dataset.toggleFor);
                if (!field) { return; }
                var show = field.type === 'password';
                field.type = show ? 'text' : 'password';
                var open = toggle.querySelector('.eye-open');
                var closed = toggle.querySelector('.eye-off');
                if (show) {
                    open.removeAttribute('hidden');
                    closed.setAttribute('hidden', 'hidden');
                } else {
                    open.setAttribute('hidden', 'hidden');
                    closed.removeAttribute('hidden');
                }
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                field.focus({ preventScroll: true });
            });
        });
    })();
</script>
</body>
</html>
