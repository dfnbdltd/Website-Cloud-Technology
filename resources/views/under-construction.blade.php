<!doctype html>
<html lang="en">
<head>@include('partials.head', ['title' => 'Coming soon'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}"></head>
<body class="domain-app">
@include('partials.sidebar', ['active' => 'construction', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => 'Under construction', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2))])
<div class="domain-content standalone-content">
<div class="construction-card">
<span class="construction-icon">🚧</span>
<span class="domain-kicker">UNDER CONSTRUCTION</span>
<h1 class="workspace-title">{{ $section ?? "We're building this section." }} is coming soon.</h1>
<p class="workspace-subtitle">This part of the client area is on its way. Meanwhile you can register domains, review invoices or open a support ticket.</p>
<div class="quick-actions construction-actions"><a href="{{ route('domains.index') }}"><b>◎</b> Register domain</a><a href="{{ route('invoices') }}"><b>▣</b> View invoices</a><a href="{{ route('support') }}"><b>◔</b> Open ticket</a></div>
</div>
</div>
</main>
</body>
</html>
