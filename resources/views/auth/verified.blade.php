<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Email verified'])
    <meta http-equiv="refresh" content="3; url={{ $destination }}">
    <style>
        .verified-icon { width:86px;height:86px;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 12px 30px rgba(34,197,94,.35);animation:co-pop .5s cubic-bezier(.22,.61,.36,1) both; }
        .verified-icon svg { width:44px;height:44px;stroke:#fff;stroke-width:3.4;fill:none;stroke-linecap:round;stroke-linejoin:round; }
        @keyframes co-pop { from{opacity:0;transform:scale(.55)} to{opacity:1;transform:scale(1)} }
        .verified-timer { display:block;margin-top:16px;font-size:12.5px;color:#8aa0b8; }
        @media (prefers-reduced-motion:reduce) { .verified-icon { animation:none; } }
    </style>
</head>
<body class="auth-page"><div class="auth-wrap"><a class="brand auth-brand" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'v'])</a><div class="login-panel verify-panel"><div class="verified-icon"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div><div class="eyebrow">All done</div><h1>Email verified!</h1><p>Welcome, <b>{{ $name }}</b> — your email address is confirmed and your account is active. Taking you back to where you left off…</p><div class="login-note"><a class="button form-button" href="{{ $destination }}" style="text-decoration:none">Continue →</a></div><small class="verified-timer">Redirecting automatically in a few seconds — click Continue if nothing happens.</small></div>@include('partials.chat')</div></body>
</html>
