<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Verify your email'])</head>
<body class="auth-page"><div class="auth-wrap"><a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'v'])</a><div class="login-panel verify-panel"><div class="verify-icon">✉</div><div class="eyebrow">One last step</div><h1>Check your email.</h1><p>We sent a verification link to @if($email)<b>{{ $email }}</b>@else your email address @endif. Click it to activate your account — we'll then email you your login details.</p>@if(session('success'))<div class="form-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('verification.resend') }}">@csrf<label>Didn't get it? Check spam, or resend to<input type="email" name="email" value="{{ $email }}" placeholder="you@company.com" required></label><button class="button form-button" type="submit">Resend verification email <span>↻</span></button></form><div class="login-note">Already verified? <a href="{{ route('login', empty($returnTo) ? [] : ['return_to' => $returnTo]) }}">Sign in</a></div></div><a class="back-link" href="{{ route('landing') }}">← Back to homepage</a></div>
@include('partials.chat')
</body>
</html>
