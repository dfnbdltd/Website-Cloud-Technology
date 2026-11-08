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

    .reg4-wrap{position:relative;z-index:1;max-width:940px;margin:auto;padding:20px 24px 30px}
    .reg4-brand{justify-content:center;margin-bottom:12px}
    .reg4-card{position:relative;background:#fff;border:1px solid #e8edf3;border-radius:22px;
        box-shadow:0 24px 60px -28px rgba(16,44,79,.16),0 8px 24px -16px rgba(16,44,79,.06);
        overflow:hidden;animation:reg4In .45s ease both}
    .reg4-card:before{content:'';position:absolute;top:0;left:0;right:0;height:4px;z-index:1;
        background:linear-gradient(90deg,var(--blue),#58a9ff 55%,var(--green))}
    @keyframes reg4In{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}

    /* Header */
    .reg4-head{padding:22px 34px 14px;border-bottom:1px solid #edf1f5;background:#fff}
    .reg4-eyebrow{display:inline-flex;align-items:center;gap:9px;padding:8px 15px;border-radius:999px;background:var(--sky);font-size:11.5px}
    .reg4-eyebrow i{width:8px;height:8px;border-radius:50%;background:#3ac18a;box-shadow:0 0 0 4px rgba(58,193,138,.18)}
    .reg4-head h1{font:800 38px/1.08 'Manrope',sans-serif;letter-spacing:-1.6px;margin:13px 0 6px;color:var(--ink)}
    .reg4-head h1 span{color:var(--blue)}
    .reg4-head p{color:var(--muted);font-size:15px;line-height:1.5;margin:0;max-width:640px}

    /* Alerts */
    .reg4-alert{display:flex;align-items:flex-start;gap:12px;margin:20px 50px 0;padding:14px 16px;border-radius:11px;
        font-size:14px;line-height:1.55}
    .reg4-alert svg{flex:none;width:19px;height:19px;margin-top:2px}
    .reg4-alert strong{display:block;font-size:11.5px;letter-spacing:.5px;text-transform:uppercase;opacity:.8;margin-bottom:2px}
    .reg4-alert-error{background:#fff7f5;border:1px solid #f3ddd8;color:#ad625a}
    .reg4-alert-success{background:#f2fbf7;border:1px solid #ccebdd;color:#197456}

    /* Form shell + sections */
    .reg4-form{padding:16px 34px 22px}
    .reg4-form fieldset{min-width:0;border:1px solid #e5eaf0;border-radius:14px;padding:4px 18px 1px;margin:0 0 10px;background:#fcfdff;transition:border-color .18s ease,box-shadow .18s ease}
    .reg4-form fieldset:focus-within{border-color:#cfddec;box-shadow:0 4px 18px -14px rgba(47,111,214,.28)}
    .reg4-form legend{display:flex;align-items:center;gap:10px;padding:0 9px;color:#243b58;
        font:800 13.5px 'Manrope',sans-serif;letter-spacing:.8px;text-transform:uppercase}
    .reg4-step{display:grid;place-items:center;width:26px;height:26px;border:1px solid #d8e6f7;border-radius:8px;background:#f0f6fd;
        color:#3572c4;font:800 12px/1 'Manrope',sans-serif;letter-spacing:0}
    .reg4-note{color:var(--muted);font-size:13px;line-height:1.55;margin:9px 0 15px}

    .reg4-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 18px;margin-top:8px}
    .reg4-span{grid-column:1 / -1}

    /* Fields */
    .reg4-field{display:block;margin:0 0 9px;color:#40536b;font-size:14px;font-weight:700;letter-spacing:.2px}
    .reg4-field>span{display:block;margin-bottom:5px}
    .reg4-inline-hint{margin-left:8px;color:#8fa2b6;font-size:12px;font-weight:500;letter-spacing:0}
    .reg4-req{color:#d96b63;margin-left:2px}
    .reg4-opt{color:#8fa2b6;font-weight:500;font-size:12.5px;margin-left:4px}
    .reg4-field small.reg4-hint{display:block;margin-top:7px;color:#8fa2b6;font-size:12.5px;font-weight:500;line-height:1.5}
    .reg4-field-error{display:block;min-height:0;margin-top:6px;color:#b34843;font-size:12.5px;font-weight:600;line-height:1.4}
    .reg4-field-error:empty{display:none}
    .reg4-field.is-invalid>span{color:#b34843}
    .reg4-form input.is-invalid,.reg4-form select.is-invalid,
    .reg4-form input.is-invalid:focus,.reg4-form select.is-invalid:focus{border-color:#d25b55;box-shadow:0 0 0 3px rgba(210,91,85,.12);background:#fffafa}
    .reg4-form .reg4-check.is-invalid{color:#b34843}
    .reg4-form .reg4-check.is-invalid input{accent-color:#c64f49;outline:2px solid rgba(198,79,73,.28);outline-offset:2px}
    .reg4-shake{animation:reg4-shake .42s ease-in-out}
    @keyframes reg4-shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-7px)}40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(3px)}}
    @media(prefers-reduced-motion:reduce){.reg4-shake{animation:none}}

    .reg4-form input[type=text],.reg4-form input[type=email],.reg4-form input[type=tel],
    .reg4-form input[type=password],.reg4-form select{width:100%;display:block;border:1px solid #dbe6f0;border-radius:11px;
        padding:9px 13px;outline:none;font:16px 'DM Sans',sans-serif;background:#fff;color:#243b58;
        transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
    .reg4-form input:focus,.reg4-form select:focus{border-color:#6f9edb;box-shadow:0 0 0 3px rgba(47,111,214,.1);background:#fff}
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
    .reg4-pw .password-strength{margin:7px 0 0}
    .reg4-pw .password-match-status{margin-top:7px}

    /* Checkboxes — input first, label text after */
    .reg4-checks{display:grid;grid-template-columns:1fr;gap:6px;margin:1px 0 10px}
    .reg4-check{display:flex;align-items:flex-start;flex-wrap:wrap;gap:10px;padding:0;cursor:pointer;color:#5f738a;font-size:14px;font-weight:500;line-height:1.55}
    .reg4-check .reg4-field-error{flex-basis:calc(100% - 28px);margin-left:28px;margin-top:-3px}
    .reg4-check:hover{color:#40536b}
    .reg4-check input{flex:none;width:18px;height:18px;margin:2px 0 0;accent-color:var(--blue);cursor:pointer}
    .reg4-check:has(input:checked){border-color:#bcd8fb;background:#f4f9ff}
    .reg4-check strong{color:var(--ink);font-weight:700}
    .reg4-check a{color:var(--blue);font-weight:700}

    /* Trust note under the submit button */
    .reg4-secure{display:flex;align-items:center;justify-content:center;gap:7px;margin:11px 0 0;
        color:#8fa2b6;font-size:12.5px;line-height:1.5;text-align:center}
    .reg4-secure svg{flex:none;width:15px;height:15px}
    .cf-turnstile{display:flex;justify-content:center;margin:0 auto 14px}

    /* Submit + footer */
    .reg4-submit{width:100%;padding:11px 18px;border:0;border-radius:11px;cursor:pointer;display:inline-flex;
        align-items:center;justify-content:center;gap:11px;color:#fff;font:700 17px 'DM Sans',sans-serif;
        background:linear-gradient(180deg,#397bd8,#2f6fd6);box-shadow:0 10px 22px rgba(47,111,214,.2);
        transition:transform .15s ease,box-shadow .15s ease}
    .reg4-submit:hover{transform:translateY(-1px);box-shadow:0 18px 34px rgba(36,117,232,.3)}
    .reg4-submit:active{transform:none}
    .reg4-submit:focus-visible{outline:2px solid #0f4a8a;outline-offset:2px}

    .reg4-signin{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:10px 0 0;
        padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:#f6f9fd;color:#5f738a;font-size:14px}
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
        .reg4-wrap{padding:18px 14px 30px}
        .reg4-brand{margin-bottom:18px}
        .reg4-card{border-radius:16px}
        .reg4-head{padding:20px 18px 13px}
        .reg4-head h1{font-size:32px;letter-spacing:-1.2px}
        .reg4-head p{font-size:14.5px}
        .reg4-form{padding:14px 16px 20px}
        .reg4-alert{margin:12px 16px 0;font-size:13px}
        .reg4-form fieldset{padding:4px 13px 3px;margin-bottom:11px}
        .reg4-form legend{font-size:12.5px}
        .reg4-field{font-size:14px;margin-bottom:9px}
        .reg4-form input[type=text],.reg4-form input[type=email],.reg4-form input[type=tel],
        .reg4-form input[type=password],.reg4-form select{font-size:16px;padding:9px 12px}
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

        <form class="reg4-form" method="POST" action="{{ route('register.submit') }}" data-password-strength novalidate>
            @csrf

            {{-- 1. Personal information --}}
            <fieldset>
                <legend><span class="reg4-step">1</span> Personal information</legend>
                <div class="reg4-grid">
                    <label class="reg4-field reg4-span {{ $errors->has('name') ? 'is-invalid' : '' }}">
                        <span>Full Name <em class="reg4-req">*</em></span>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="{{ $errors->has('name') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="error-name"
                            placeholder="Your full name" autocomplete="name" required autofocus>
                        <small class="reg4-field-error" id="error-name" data-field-error="name">@error('name'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field reg4-span {{ $errors->has('email') ? 'is-invalid' : '' }}">
                        <span>Email Address <em class="reg4-req">*</em><small class="reg4-inline-hint">This becomes your login ID &mdash; we'll send a verification link here.</small></span>
                        <input type="email" name="email" value="{{ old('email') }}" class="{{ $errors->has('email') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="error-email" placeholder="you@company.com" autocomplete="email" required>
                        <small class="reg4-field-error" id="error-email" data-field-error="email">@error('email'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field reg4-span {{ $errors->has('phone') ? 'is-invalid' : '' }}">
                        <span>Phone Number <em class="reg4-req">*</em></span>
                        <div class="reg4-phone">
                            <select name="phone_dial" aria-label="Country dial code">
                                <option value="+880" {{ old('phone_dial', '+880') === '+880' ? 'selected' : '' }}>+880</option>
                                <option value="+91" {{ old('phone_dial') === '+91' ? 'selected' : '' }}>+91</option>
                                <option value="+44" {{ old('phone_dial') === '+44' ? 'selected' : '' }}>+44</option>
                                <option value="+1" {{ old('phone_dial') === '+1' ? 'selected' : '' }}>+1</option>
                            </select>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="{{ $errors->has('phone') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="error-phone" placeholder="1XXXXXXXXX" inputmode="numeric" pattern="[0-9]*" autocomplete="tel-national" required data-numeric-only>
                        </div>
                        <small class="reg4-field-error" id="error-phone" data-field-error="phone">@error('phone'){{ $message }}@enderror</small>
                    </label>
                </div>
            </fieldset>

            {{-- 2. Billing address --}}
            <fieldset>
                <legend><span class="reg4-step">2</span> Billing address</legend>
                <div class="reg4-grid">
                    <label class="reg4-field {{ $errors->has('company_name') ? 'is-invalid' : '' }}">
                        <span>Company Name <small class="reg4-opt">(Optional)</small></span>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" class="{{ $errors->has('company_name') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('company_name') ? 'true' : 'false' }}" aria-describedby="error-company_name" placeholder="Company name" autocomplete="organization">
                        <small class="reg4-field-error" id="error-company_name" data-field-error="company_name">@error('company_name'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field {{ $errors->has('country') ? 'is-invalid' : '' }}">
                        <span>Country <em class="reg4-req">*</em></span>
                        <select name="country" class="{{ $errors->has('country') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('country') ? 'true' : 'false' }}" aria-describedby="error-country" autocomplete="country-name" required>
                            <option value="">Choose country</option>
                            <option value="Bangladesh" {{ old('country', 'Bangladesh') === 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                            <option value="India" {{ old('country') === 'India' ? 'selected' : '' }}>India</option>
                            <option value="Pakistan" {{ old('country') === 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
                            <option value="United States" {{ old('country') === 'United States' ? 'selected' : '' }}>United States</option>
                            <option value="United Kingdom" {{ old('country') === 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                            <option value="Other" {{ old('country') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <small class="reg4-field-error" id="error-country" data-field-error="country">@error('country'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field reg4-span {{ $errors->has('address1') ? 'is-invalid' : '' }}">
                        <span>Street Address <em class="reg4-req">*</em></span>
                        <input type="text" name="address1" value="{{ old('address1') }}" class="{{ $errors->has('address1') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('address1') ? 'true' : 'false' }}" aria-describedby="error-address1" placeholder="House, road, area" autocomplete="address-line1" required>
                        <small class="reg4-field-error" id="error-address1" data-field-error="address1">@error('address1'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field {{ $errors->has('city') ? 'is-invalid' : '' }}">
                        <span>City <em class="reg4-req">*</em></span>
                        <input type="text" name="city" value="{{ old('city') }}" class="{{ $errors->has('city') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('city') ? 'true' : 'false' }}" aria-describedby="error-city" placeholder="City" autocomplete="address-level2" required>
                        <small class="reg4-field-error" id="error-city" data-field-error="city">@error('city'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field {{ $errors->has('postcode') ? 'is-invalid' : '' }}">
                        <span>Postcode <em class="reg4-req">*</em></span>
                        <input type="text" name="postcode" value="{{ old('postcode') }}" class="{{ $errors->has('postcode') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('postcode') ? 'true' : 'false' }}" aria-describedby="error-postcode" placeholder="Postcode" autocomplete="postal-code" required>
                        <small class="reg4-field-error" id="error-postcode" data-field-error="postcode">@error('postcode'){{ $message }}@enderror</small>
                    </label>
                </div>
                <input type="hidden" name="currency" value="BDT">
            </fieldset>

            {{-- 3. Account security --}}
            <fieldset>
                <legend><span class="reg4-step">3</span> Account security</legend>
                <div class="reg4-grid">
                    <label class="reg4-field reg4-span {{ $errors->has('password') ? 'is-invalid' : '' }}">
                        <span>Password <em class="reg4-req">*</em></span>
                        <div class="reg4-pw">
                            <input type="password" name="password" id="regPass" class="{{ $errors->has('password') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="error-password" placeholder="Create a strong password" autocomplete="new-password" required>
                            <button type="button" class="reg4-eye" data-toggle-for="regPass" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                            </button>
                        </div>
                        <small class="reg4-field-error" id="error-password" data-field-error="password">@error('password'){{ $message }}@enderror</small>
                    </label>
                    <label class="reg4-field reg4-span {{ $errors->has('password') ? 'is-invalid' : '' }}">
                        <span>Confirm Password <em class="reg4-req">*</em></span>
                        <div class="reg4-pw">
                            <input type="password" name="password_confirmation" id="regConfirm" class="{{ $errors->has('password') ? 'is-invalid' : '' }}" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="error-password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required>
                            <button type="button" class="reg4-eye" data-toggle-for="regConfirm" aria-label="Show password" aria-pressed="false">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                            </button>
                        </div>
                        <small class="reg4-field-error" id="error-password_confirmation" data-field-error="password_confirmation">@error('password'){{ $message }}@enderror</small>
                    </label>
                </div>
            </fieldset>

            <div class="reg4-checks">
                <label class="reg4-check {{ $errors->has('terms') ? 'is-invalid' : '' }}">
                    <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} aria-invalid="{{ $errors->has('terms') ? 'true' : 'false' }}" aria-describedby="error-terms" required>
                    <span>I have read and agree to the <a href="{{ route('under.construction', 'terms') }}">Terms of Service</a>.</span>
                    <small class="reg4-field-error" id="error-terms" data-field-error="terms">@error('terms'){{ $message }}@enderror</small>
                </label>
            </div>

            @if(config('services.turnstile.site_key') && config('services.turnstile.secret_key'))
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                @error('captcha')
                    <small class="reg4-hint">{{ $message }}</small>
                @enderror
            @endif

            <p class="reg4-secure">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <span>Your details are encrypted and used only for billing and support.</span>
            </p>

            <button class="reg4-submit" type="submit">Create account <span aria-hidden="true">&rarr;</span></button>

            <p class="reg4-signin">
                <span>Already registered? <a href="{{ route('login', empty($returnTo) ? [] : ['return_to' => $returnTo]) }}">Sign in</a></span>
                <span><a href="{{ route('password.request') }}">Forgot password?</a></span>
            </p>
        </form>
    </main>

    <a class="reg4-back" href="{{ route('landing') }}">&larr; Back to homepage</a>
</div>

@include('partials.chat')
@if(config('services.turnstile.site_key') && config('services.turnstile.secret_key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
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

        var numericFields = document.querySelectorAll('[data-numeric-only]');
        Array.prototype.forEach.call(numericFields, function (field) {
            field.addEventListener('input', function () {
                var digitsOnly = field.value.replace(/\D/g, '');
                if (field.value !== digitsOnly) {
                    field.value = digitsOnly;
                }
            });
        });

        var form = document.querySelector('.reg4-form');
        if (!form) { return; }

        var fields = form.querySelectorAll('input, select');
        var firstInvalidField = function () {
            return form.querySelector('.reg4-field.is-invalid input, .reg4-field.is-invalid select, .reg4-check.is-invalid input');
        };
        var shakeForm = function () {
            form.classList.remove('reg4-shake');
            void form.offsetWidth;
            form.classList.add('reg4-shake');
        };
        var clearClientError = function (field) {
            if (field.dataset.clientInvalid !== 'true') { return; }
            var wrapper = field.closest('.reg4-field, .reg4-check');
            var message = form.querySelector('[data-field-error="' + field.name + '"]');
            field.classList.remove('is-invalid');
            field.setAttribute('aria-invalid', 'false');
            if (wrapper) { wrapper.classList.remove('is-invalid'); }
            if (message) {
                message.textContent = '';
            }
            delete field.dataset.clientInvalid;
        };
        var setClientError = function (field, messageText) {
            var wrapper = field.closest('.reg4-field, .reg4-check');
            var message = form.querySelector('[data-field-error="' + field.name + '"]');
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
            field.dataset.clientInvalid = 'true';
            if (wrapper) { wrapper.classList.add('is-invalid'); }
            if (message) { message.textContent = messageText; }
        };
        var errorMessage = function (field) {
            if (field.validity.valueMissing) { return 'This field is required.'; }
            if (field.validity.typeMismatch) { return 'Please enter a valid email address.'; }
            if (field.validity.patternMismatch) { return 'Please enter numbers only.'; }
            return 'Please check this field.';
        };

        Array.prototype.forEach.call(fields, function (field) {
            field.addEventListener('input', function () { clearClientError(field); });
            field.addEventListener('change', function () { clearClientError(field); });
        });

        form.addEventListener('submit', function (event) {
            var invalid = [];
            Array.prototype.forEach.call(fields, function (field) {
                clearClientError(field);
                if (!field.checkValidity()) {
                    invalid.push(field);
                    setClientError(field, errorMessage(field));
                }
            });
            if (!invalid.length) { return; }
            event.preventDefault();
            shakeForm();
            invalid[0].focus({ preventScroll: true });
            invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        if (firstInvalidField()) {
            shakeForm();
            firstInvalidField().focus({ preventScroll: true });
            firstInvalidField().scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    })();
</script>
</body>
</html>
