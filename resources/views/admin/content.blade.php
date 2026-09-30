<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'Site content'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}"></head><body class="domain-app">@include('partials.admin-sidebar', ['active' => 'content'])
<main class="domain-main"><header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>Site content</strong></div><a class="cart-badge" href="{{ route('landing') }}" target="_blank">View website ↗</a></header>
<div class="domain-content standalone-content">
<span class="domain-kicker">CONTENT MANAGEMENT</span><h1 class="workspace-title">Site content.</h1><p class="workspace-subtitle">Edit the text on the public website and in customer emails. Leave a field empty to use the default shown in grey.</p>
@if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.content.update') }}">@csrf
@foreach($groups as $groupName => $fields)
<section class="store-panel cms-group"><h2>{{ $groupName }}</h2><div class="store-fields">
@foreach($fields as $key => $field)
<label class="{{ $field[2] === 'textarea' ? 'cms-wide' : '' }}">{{ $field[0] }}
@if($field[2] === 'textarea')<textarea name="{{ $key }}" rows="3" placeholder="{{ $field[1] }}">{{ old($key, $values[$key] ?? '') }}</textarea>@else<input type="text" name="{{ $key }}" value="{{ old($key, $values[$key] ?? '') }}" placeholder="{{ $field[1] }}">@endif
</label>
@endforeach
</div></section>
@endforeach
<div class="cms-save"><button class="checkout-submit" type="submit">Save site content <b>→</b></button></div>
</form>
</div></main>
<style>.cms-group{margin-top:20px}.cms-group h2{margin:0 0 14px}.cms-wide{grid-column:1/-1}.cms-group textarea{display:block;width:100%;box-sizing:border-box;margin-top:6px;border:1px solid var(--line);border-radius:8px;padding:10px 12px;font:14px 'DM Sans',sans-serif;resize:vertical}.cms-save{position:sticky;bottom:0;padding:16px 0;background:linear-gradient(#f7f9fc00,#f7f9fc 40%)}</style>
</body></html>
