<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Reset password'])</head>
<body class="auth-page">
<div class="auth-wrap">
    <a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'rp'])</a>
    <div class="login-panel">
        <div class="eyebrow">Account recovery</div>
        <h1>Choose a new password.</h1>
        <p>Use at least 8 characters, including uppercase and lowercase letters, a number, and a special character.</p>
        @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('password.update') }}" data-password-strength>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>Email address<input type="email" name="email" value="{{ old('email', $email) }}" required></label>
            <label>New password<input type="password" name="password" placeholder="8+ chars: uppercase, lowercase, number & symbol" required autocomplete="new-password"><small class="field-hint">Use at least 8 characters, including uppercase and lowercase letters, a number, and a special character.</small></label>
            <label>Confirm new password<input type="password" name="password_confirmation" placeholder="Re-enter your password" required autocomplete="new-password"></label>
            <button class="button form-button" type="submit">Reset password <span>↗</span></button>
        </form>
        <div class="login-note"><a href="{{ route('login') }}">← Back to sign in</a></div>
    </div>
    <a class="back-link" href="{{ route('landing') }}">← Back to homepage</a>
</div>
</body>
</html>
