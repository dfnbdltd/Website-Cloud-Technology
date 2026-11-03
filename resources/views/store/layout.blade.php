<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $pageTitle])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ @md5_file(public_path('css/store.css')) }}">
</head>
<body class="domain-app">
@include('partials.sidebar', ['active' => $navActive, 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">
    @include('partials.topbar', ['title' => $topbarTitle, 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
    <div class="domain-content standalone-content store-page">
        @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
        @yield('store')
    </div>
</main>
@include('partials.chat')
</body>
</html>
