<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Profile settings'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}"></head><body class="domain-app">@include('partials.sidebar', ['active' => 'settings', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'Profile settings', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">CUSTOMER PROFILE</span><h1 class="workspace-title">Profile settings.</h1><p class="workspace-subtitle">Keep your billing details current. Your unique ID stays with your account forever.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

<div class="profile-id-card">
<div class="profile-id-avatar">
@if($user->logo)<img src="{{ asset('storage/' . $user->logo) }}" alt="{{ $user->name }}">@else<span>{{ \App\User::initials($user->name) }}</span>@endif
</div>
<div><strong>{{ $user->name }}</strong><span>{{ $user->email }}</span><span class="profile-uid">Unique ID: <b>{{ $user->customer_uid }}</b></span></div>
</div>

<form class="store-panel profile-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf
<h2>Account information</h2>
<div class="store-fields">
<label>First name *<input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required></label>
<label>Last name *<input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required></label>
<label>Registered email<input type="email" value="{{ $user->email }}" disabled></label>
<label>Phone *<input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required></label>
<label>Company name<input type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}"></label>
<label>Street address *<input type="text" name="address1" value="{{ old('address1', $user->address1) }}" required></label>
<label>Street address 2<input type="text" name="address2" value="{{ old('address2', $user->address2) }}"></label>
<label>City *<input type="text" name="city" value="{{ old('city', $user->city) }}" required></label>
<label>State / Division<input type="text" name="state" value="{{ old('state', $user->state) }}"></label>
<label>Postcode *<input type="text" name="postcode" value="{{ old('postcode', $user->postcode) }}" required></label>
<label>Country *<select name="country" required>@foreach(['Bangladesh', 'India', 'Pakistan', 'United States', 'United Kingdom', 'Other'] as $countryOption)<option value="{{ $countryOption }}" {{ (old('country', $user->country) === $countryOption) ? 'selected' : '' }}>{{ $countryOption }}</option>@endforeach</select></label>
<label>Profile logo<br><input type="file" name="logo" accept="image/*"></label>
</div>
<button class="checkout-submit" type="submit">Save changes <b>→</b></button>
</form>
</div>
</main>
@include('partials.chat')
</body></html>
