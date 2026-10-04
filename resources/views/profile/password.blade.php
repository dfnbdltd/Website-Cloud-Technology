<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Change password'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'password', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'Change password', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">ACCOUNT SECURITY</span><h1 class="workspace-title">Change password.</h1><p class="workspace-subtitle">Use at least 8 characters with uppercase and lowercase letters, a number, and a special character. You stay signed in on this device after changing it.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

<form class="store-panel profile-form" method="POST" action="{{ route('change.password.submit') }}" data-password-strength>@csrf
<h2>New password</h2>
<label class="stack-label">Current password *<input type="password" name="current_password" required></label>
<div class="store-fields">
<label>New password *<input type="password" name="password" placeholder="8+ chars: uppercase, lowercase, number & symbol" required></label>
<label>Confirm new password *<input type="password" name="password_confirmation" placeholder="Repeat your new password" required></label>
</div>
<button class="checkout-submit" type="submit">Update password <b>→</b></button>
</form>
</div>
</main>
@include('partials.chat')
</body></html>
