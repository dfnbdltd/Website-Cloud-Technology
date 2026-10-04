<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Forgot password'])</head>
<body class="auth-page">
<div class="auth-wrap">
    <a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'fp'])</a>
    <div class="login-panel">
        <div class="eyebrow">Account recovery</div>
        <h1>Forgot your password?</h1>
        <p>Enter your account email and we’ll send you a secure password reset link.</p>
        @if(session('status'))<div class="form-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus></label>
            <button class="button form-button" type="submit">Send reset link <span>→</span></button>
        </form>
        <div class="login-note"><a href="{{ route('login') }}">← Back to sign in</a></div>
    </div>
    <a class="back-link" href="{{ route('landing') }}">← Back to homepage</a>
</div>
</body>
</html>
