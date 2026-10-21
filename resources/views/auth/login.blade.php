<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Client login'])
<style>
    /* Login-only page accents (design system styles live in public/css/app.css, scoped to .login-v2) */
    .login-bg{position:fixed;inset:0;z-index:0;overflow:hidden;background:linear-gradient(160deg,#f6fbff 0%,#eef5ff 48%,#f7fbff 100%)}
    .login-bg:before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(36,117,232,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(36,117,232,.06) 1px,transparent 1px);background-size:34px 34px}
    .login-bg i{position:absolute;border-radius:50%;filter:blur(70px)}
    .login-bg i:nth-child(1){width:430px;height:430px;background:rgba(36,117,232,.14);top:-150px;left:-130px}
    .login-bg i:nth-child(2){width:360px;height:360px;background:rgba(88,169,255,.12);bottom:-140px;right:-110px}
    .login-bg i:nth-child(3){width:210px;height:210px;background:rgba(54,184,134,.09);top:56%;left:60%}
</style>
</head>
<body class="auth-page login-v2">
<div class="login-bg" aria-hidden="true"><i></i><i></i><i></i></div>
<div class="auth-wrap">
    <a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'l'])</a>

    <div class="login-panel">
        <div class="eyebrow login-eyebrow"><i></i>Secure client area</div>
        <h1>Welcome back<span>.</span></h1>
        <p>Sign in to manage your hosting, domains and support requests.</p>

        @if(session('success'))
            <div class="form-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="form-error form-error-icon" role="alert">
                <span>{{ $errors->first() }}</span>
                @if(session('unverified_email'))
                    <form class="inline-resend" method="POST" action="{{ route('verification.resend') }}">@csrf
                        <input type="hidden" name="email" value="{{ session('unverified_email') }}">
                        <button type="submit">Resend verification email</button>
                    </form>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <label for="loginEmail">Email Address <em>(which will be your Login ID)</em>
                <input type="email" name="email" id="loginEmail" value="{{ old('email') }}" placeholder="you@company.com" autocomplete="email" required autofocus>
            </label>
            <label for="loginPassword">Password
                <div class="password-wrap">
                    <input type="password" name="password" id="loginPassword" placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                    </button>
                </div>
            </label>
            <div class="form-meta">
                <label class="check"><input type="checkbox" name="remember"> Remember me</label>
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <button class="button form-button" type="submit">Sign in <span>&rarr;</span></button>
        </form>

        <div class="auth-divider"><span>New to CloudTech?</span></div>

        <a class="button form-button signup-button" href="{{ route('register') }}">Create a free account <span>&rarr;</span></a>

        @if(count(session('domain_cart', [])))
            <p class="auth-cart-note">&#128722; Your cart ({{ count(session('domain_cart', [])) }}) is saved &mdash; you'll continue to checkout after signing in.</p>
        @endif
    </div>

    <a class="back-link" href="{{ route('landing') }}">&larr; Back to homepage</a>
</div>
<script>
    (function () {
        var toggle = document.getElementById('passwordToggle');
        var field = document.getElementById('loginPassword');
        if (!toggle || !field) { return; }
        toggle.addEventListener('click', function () {
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
    })();
</script>
@include('partials.chat')
</body>
</html>
