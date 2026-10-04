<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Create account'])</head>
<body class="auth-page">
<div class="auth-wrap auth-wrap-wide">
    <a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'r'])</a>
    <div class="login-panel register-panel">
        <div class="eyebrow eyebrow-center">Create Customer account</div>
        <h1>Register.</h1>
        <p>All fields marked with <b>*</b> are required. Your information is encrypted and never shared.</p>
        @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('register.submit') }}" data-password-strength>
            @csrf
            <div class="register-note-aside">🛡 <span>Already have an account with us? Use the same email — your domains, invoices and tickets all live under one login.</span></div>

            <fieldset class="form-section">
                <legend>Personal Information</legend>
                <div class="field-grid">
                    <label>First Name *<input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Your first name" required autofocus></label>
                    <label>Last Name *<input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Your last name" required></label>
                </div>
                <label>Email Address *<input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required></label>
                <label>Phone Number *
                    <div class="phone-wrap">
                        <select name="phone_dial" aria-label="Country dial code"><option value="+880" selected>+880</option><option value="+91">+91</option><option value="+44">+44</option><option value="+1">+1</option></select>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="1XXXXXXXXX" required>
                    </div>
                </label>
            </fieldset>

            <fieldset class="form-section">
                <legend>Billing Address</legend>
                <label>Company Name (Optional)<input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Company name"></label>
                <label>Street Address *<input type="text" name="address1" value="{{ old('address1') }}" placeholder="Street address" required></label>
                <label>Street Address 2 (Optional)<input type="text" name="address2" value="{{ old('address2') }}" placeholder="Apartment, suite, unit etc."></label>
                <div class="field-grid">
                    <label>City *<input type="text" name="city" value="{{ old('city') }}" placeholder="City" required></label>
                    <label>Postcode *<input type="text" name="postcode" value="{{ old('postcode') }}" placeholder="Postcode" required></label>
                </div>
                <div class="field-grid">
                    <label>Country *
                        <select name="country" required>
                            <option value="">Choose country</option>
                            <option value="Bangladesh" {{ old('country', 'Bangladesh') === 'Bangladesh' ? 'selected' : '' }}>Bangladesh</option>
                            <option value="India" {{ old('country') === 'India' ? 'selected' : '' }}>India</option>
                            <option value="Pakistan" {{ old('country') === 'Pakistan' ? 'selected' : '' }}>Pakistan</option>
                            <option value="United States" {{ old('country') === 'United States' ? 'selected' : '' }}>United States</option>
                            <option value="United Kingdom" {{ old('country') === 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
                            <option value="Other" {{ old('country') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </label>
                    <label>State<input type="text" name="state" value="{{ old('state') }}" placeholder="State / Division"></label>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Additional Information</legend>
                <label>How did you find us?
                    <select name="found_us">
                        <option value="">Choose one</option>
                        <option value="Google Search" {{ old('found_us') === 'Google Search' ? 'selected' : '' }}>Google Search</option>
                        <option value="Social Media" {{ old('found_us') === 'Social Media' ? 'selected' : '' }}>Social Media</option>
                        <option value="Friend or Colleague" {{ old('found_us') === 'Friend or Colleague' ? 'selected' : '' }}>Friend or Colleague</option>
                        <option value="Advertisement" {{ old('found_us') === 'Advertisement' ? 'selected' : '' }}>Advertisement</option>
                        <option value="Other" {{ old('found_us') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </label>
                <label>Choose Currency *
                    <select name="currency" required><option value="BDT" {{ old('currency', 'BDT') === 'BDT' ? 'selected' : '' }}>BDT — Bangladeshi Taka (৳)</option></select>
                    <small class="field-hint">BDT is the only supported currency.</small>
                </label>
            </fieldset>

            <fieldset class="form-section">
                <legend>Account Security</legend>
                <div class="field-grid">
                    <label>Password *
                        <input type="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
                    </label>
                    <label>Confirm Password *
                        <input type="password" name="password_confirmation" placeholder="Repeat your password" required autocomplete="new-password">
                    </label>
                </div>
                <label>Security Question *
                    <select name="security_question" required>
                        <option value="">Please choose one</option>
                        <option value="What is your mother's maiden name?" {{ old('security_question') === "What is your mother's maiden name?" ? 'selected' : '' }}>What is your mother's maiden name?</option>
                        <option value="What was the name of your first school?" {{ old('security_question') === 'What was the name of your first school?' ? 'selected' : '' }}>What was the name of your first school?</option>
                        <option value="What is your favourite sports team?" {{ old('security_question') === 'What is your favourite sports team?' ? 'selected' : '' }}>What is your favourite sports team?</option>
                        <option value="What was your first pet's name?" {{ old('security_question') === "What was your first pet's name?" ? 'selected' : '' }}>What was your first pet's name?</option>
                        <option value="What city were you born in?" {{ old('security_question') === 'What city were you born in?' ? 'selected' : '' }}>What city were you born in?</option>
                    </select>
                </label>
                <label>Security Answer *<input type="text" name="security_answer" value="{{ old('security_answer') }}" placeholder="Please enter an answer" required></label>
            </fieldset>

            <label class="check register-check register-check-line"><input type="checkbox" name="newsletter" value="1" {{ old('newsletter') ? 'checked' : '' }}><span>Join our mailing list — occasional news, information and special offers by email. Unsubscribe any time.</span></label>
            <label class="check register-check register-check-line"><input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required><span>I have read and agree to the <a href="#">Terms of Service</a></span></label>
            <button class="button form-button" type="submit">Create Account <span>↗</span></button>
        </form>
        <div class="login-note">Already registered with us? <a href="{{ route('login') }}">Sign In</a> Or <a href="{{ route('password.request') }}">Reset Password</a></div>
    </div>
    <a class="back-link" href="{{ route('landing') }}">← Back to homepage</a>
</div>
</body>
</html>
