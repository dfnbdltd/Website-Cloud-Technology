<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Client login'])
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
</head>
<body class="login-page">
<header class="login-header">
    <div class="login-header-inner">
        <a class="login-brand" href="{{ route('landing') }}" aria-label="Cloud Technology — go to home page">
            @include('partials.logo', ['gid' => 'l'])
        </a>
    </div>
</header>

<main class="login-stage">
    <section class="login-card" aria-labelledby="loginTitle">
        <span class="login-eyebrow">Sign in</span>
        <h1 id="loginTitle">Insert your login credentials</h1>

        @if(session('success'))
            <div class="login-alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="login-alert error" role="alert">
                <span>{{ $errors->first() }}</span>
                @if(session('unverified_email'))
                    <form class="inline-resend" method="POST" action="{{ route('verification.resend') }}">@csrf
                        <input type="hidden" name="email" value="{{ session('unverified_email') }}">
                        <button type="submit">Resend verification email</button>
                    </form>
                @endif
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="login-field">
                <label class="login-label" for="loginEmail">Email</label>
                <input class="login-input" type="email" name="email" id="loginEmail" value="{{ old('email') }}" placeholder="you@company.com" autocomplete="email" required>
            </div>

            <div class="login-field">
                <label class="login-label" for="loginPassword">Password</label>
                <div class="login-password">
                    <input class="login-input" type="password" name="password" id="loginPassword" placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="login-eye" id="passwordToggle" aria-label="Show password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3"/><line x1="4" y1="20" x2="20" y2="4"/></svg>
                    </button>
                </div>
            </div>

            <div class="login-meta">
                <label class="login-check"><input type="checkbox" name="remember"> <span>Remember me</span></label>
                <a class="login-forgot" href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <button class="login-submit" type="submit">Sign in securely</button>
        </form>

        <p class="login-note">
            <svg class="login-note-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5" stroke-linecap="round"/><circle cx="12" cy="7.75" r="0.9" fill="currentColor" stroke="none"/></svg>
            <span>Your information is encrypted and never shared. After signing in, you'll continue to the page you were on.</span>
        </p>

        <div class="login-divider" role="presentation"></div>

        <p class="login-foot">New here? <a href="{{ route('register', empty($returnTo) ? [] : ['return_to' => $returnTo]) }}">Create an account</a></p>

        @if(count(session('domain_cart', [])))
            <p class="login-cart">Your cart ({{ count(session('domain_cart', [])) }}) is saved &mdash; you'll continue to checkout after signing in.</p>
        @endif
    </section>
</main>

<script>
    (function () {
        var toggle = document.getElementById('passwordToggle');
        var field = document.getElementById('loginPassword');
        if (!toggle || !field) { return; }
        toggle.querySelector('.eye-open').setAttribute('hidden', 'hidden');
        toggle.querySelector('.eye-off').removeAttribute('hidden');
        toggle.addEventListener('click', function () {
            var show = field.type === 'password';
            field.type = show ? 'text' : 'password';
            var open = toggle.querySelector('.eye-open');
            var closed = toggle.querySelector('.eye-off');
            if (show) {
                closed.setAttribute('hidden', 'hidden');
                open.removeAttribute('hidden');
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
