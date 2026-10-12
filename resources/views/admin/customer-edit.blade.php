<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Edit customer'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
</head>
<body class="domain-app">
@include('partials.admin-sidebar', ['active' => 'customers'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">Operations /</span><strong>Customers / Edit</strong></div></header>
    <div class="domain-content standalone-content">
        <span class="domain-kicker">CUSTOMER PROFILE</span>
        <h1 class="workspace-title">Edit customer details.</h1>
        <p class="workspace-subtitle">{{ $customer->customer_uid }} · {{ $customer->email }}</p>
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
        <form class="customer-edit-form" method="POST" action="{{ route('admin.customers.update', $customer) }}">
            @csrf
            <div class="support-layout">
                <label>First name *<input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="120" required></label>
                <label>Last name *<input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="120" required></label>
                <label>Email address *<input type="email" name="email" value="{{ old('email', $customer->email) }}" maxlength="255" required></label>
                <label>Phone number *<input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="40" required></label>
                <label>Company name<input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" maxlength="190"></label>
                <label>Currency *<input type="text" name="currency" value="{{ old('currency', $customer->currency) }}" maxlength="10" required></label>
                <label class="customer-edit-wide">Street address *<input type="text" name="address1" value="{{ old('address1', $customer->address1) }}" maxlength="190" required></label>
                <label class="customer-edit-wide">Street address 2<input type="text" name="address2" value="{{ old('address2', $customer->address2) }}" maxlength="190"></label>
                <label>City *<input type="text" name="city" value="{{ old('city', $customer->city) }}" maxlength="120" required></label>
                <label>State / division<input type="text" name="state" value="{{ old('state', $customer->state) }}" maxlength="120"></label>
                <label>Postcode *<input type="text" name="postcode" value="{{ old('postcode', $customer->postcode) }}" maxlength="30" required></label>
                <label>Country *<input type="text" name="country" value="{{ old('country', $customer->country) }}" maxlength="120" required></label>
                <label class="customer-edit-wide">How did you find us?<input type="text" name="found_us" value="{{ old('found_us', $customer->found_us) }}" maxlength="190"></label>
                <label class="customer-edit-check"><input type="checkbox" name="newsletter" value="1" {{ old('newsletter', $customer->newsletter) ? 'checked' : '' }}> Subscribe to newsletter</label>
            </div>
            <div class="customer-edit-actions">
                <a href="{{ route('admin.customers') }}">Cancel</a>
                <button type="submit">Save customer</button>
            </div>
        </form>
    </div>
</main>
<style>
.customer-edit-form{background:#fff;border:1px solid #e5ebf3;border-radius:8px;padding:24px}
.customer-edit-form .support-layout label{font-size:12px}
.customer-edit-wide{grid-column:1/-1}
.customer-edit-check{display:flex!important;align-items:center;gap:9px}
.customer-edit-check input{width:16px!important;height:16px;margin:0!important}
.customer-edit-actions{display:flex;justify-content:flex-end;align-items:center;gap:15px;margin-top:8px}
.customer-edit-actions a{color:#73849a;font-size:12px;text-decoration:none}
.customer-edit-actions button{border:0;border-radius:6px;background:#1769aa;color:#fff;padding:11px 18px;font:700 12px 'DM Sans',sans-serif;cursor:pointer}
@media(max-width:600px){.customer-edit-form{padding:16px}.customer-edit-form .support-layout{grid-template-columns:1fr}.customer-edit-wide{grid-column:auto}}
</style>
</body>
</html>
