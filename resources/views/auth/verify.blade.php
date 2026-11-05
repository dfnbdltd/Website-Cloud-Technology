<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Verify your email'])</head>
<body class="auth-page"><div class="auth-wrap"><a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'v'])</a><div class="login-panel verify-panel"><div class="verify-icon">✉</div><div class="eyebrow">One last step</div><h1>Check your email.</h1><p>We sent a verification link to @if($email)<b>{{ $email }}</b>@else your email address @endif. Click it to activate your account — we'll then email you your login details.</p>@if(session('success'))<div class="form-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('verification.resend') }}" data-resend-form>@csrf<label>Didn't get it? Check spam, or resend to<input type="email" name="email" value="{{ $email }}" placeholder="you@company.com" required></label><button class="button form-button" type="submit" data-resend-button @if($resendAvailableAt) disabled @endif> <span data-resend-label>@if($resendAvailableAt) Resend available in <span data-resend-countdown></span>@else Resend verification code @endif</span> <span>↻</span></button></form><div class="login-note">Already verified? <a href="{{ route('login', empty($returnTo) ? [] : ['return_to' => $returnTo]) }}">Sign in</a></div></div><a class="back-link" href="{{ route('landing') }}">← Back to homepage</a></div>
@include('partials.chat')
@if($resendAvailableAt)
<script>
    (function () {
        var button = document.querySelector('[data-resend-button]');
        var label = document.querySelector('[data-resend-label]');
        var countdown = document.querySelector('[data-resend-countdown]');
        var availableAt = {{ $resendAvailableAt }};
        if (!button || !label || !countdown) return;
        function updateCountdown() {
            var remaining = Math.max(0, availableAt - Math.floor(Date.now() / 1000));
            if (remaining === 0) {
                button.disabled = false;
                label.textContent = 'Resend verification code';
                return;
            }
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            countdown.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            window.setTimeout(updateCountdown, 1000);
        }
        updateCountdown();
    }());
</script>
@endif
</body>
</html>
