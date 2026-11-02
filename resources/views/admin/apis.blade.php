<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'API connections'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}?v={{ filemtime(public_path('css/workspace.css')) }}">
    <style>
        .api-integration{margin:22px 0;padding:24px;border:1px solid #e1eaf4;border-radius:16px;background:#fff;box-shadow:0 10px 28px rgba(32,64,100,.05)}
        .api-integration-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
        .api-integration h2{margin:0 0 7px;color:#18365d;font-size:20px}
        .api-integration p{margin:0;color:#71849a}
        .api-status{flex:none;padding:7px 11px;border-radius:999px;background:#eaf8f1;color:#16865d;font-size:12px;font-weight:700}
        .api-status.warning{background:#fff5e6;color:#a86a08}
        .api-section-title{margin:22px 0 10px;color:#8898aa;font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
        .api-endpoint{display:grid;grid-template-columns:58px minmax(0,1fr);gap:10px;padding:12px 0;border-top:1px solid #eef2f6}
        .api-method{align-self:start;padding:4px 6px;border-radius:5px;background:#edf4ff;color:#326fbe;text-align:center;font-size:10px;font-weight:800}
        .api-endpoint code{display:block;overflow-wrap:anywhere;color:#314969;font-size:12px}
        .api-endpoint small{display:block;margin-top:5px;color:#8493a5}
        .api-credentials{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
        .api-credential{display:flex;justify-content:space-between;gap:10px;padding:12px;border:1px solid #edf1f6;border-radius:9px}
        .api-credential code{overflow-wrap:anywhere;color:#536981;font-size:11px}
        .api-credential small{display:block;margin-bottom:5px;color:#97a5b4}
        .api-credential strong{flex:none;color:#16865d;font-size:11px}
        .api-credential strong.missing{color:#bd6a35}
        .api-security-note{margin-top:20px;padding:13px 15px;border:1px solid #dce9f7;border-radius:10px;background:#f6faff;color:#687f98;font-size:13px;line-height:1.6}
        @media(max-width:600px){.api-integration{padding:18px}.api-integration-head{display:block}.api-status{display:inline-block;margin-top:12px}}
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'apis'])
    <main class="domain-main">
        <header class="domain-topbar"><div><span class="crumb">System /</span><strong>API connections</strong></div><span class="top-avatar">A</span></header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">INTEGRATIONS</span>
            <h1 class="workspace-title">API connections</h1>
            <p class="workspace-subtitle">Endpoints used by the portal and whether their required credentials are configured.</p>

            @foreach($integrations as $integration)
                <section class="api-integration">
                    <div class="api-integration-head">
                        <div>
                            <h2>{{ $integration['name'] }}</h2>
                            <p>{{ $integration['purpose'] }}</p>
                        </div>
                        <span class="api-status {{ strpos($integration['status'], 'Needs') !== false ? 'warning' : '' }}">{{ $integration['status'] }}</span>
                    </div>

                    <h3 class="api-section-title">Endpoints</h3>
                    @foreach($integration['endpoints'] as $endpoint)
                        <div class="api-endpoint">
                            <span class="api-method">{{ $endpoint['method'] }}</span>
                            <div><code>{{ $endpoint['url'] }}</code><small>{{ $endpoint['usage'] }}</small></div>
                        </div>
                    @endforeach

                    @if(count($integration['settings']))
                        <h3 class="api-section-title">Integration settings</h3>
                        <div class="api-credentials">
                            @foreach($integration['settings'] as $setting)
                                <div class="api-credential">
                                    <div><small>Server setting</small><code>{{ $setting['name'] }}</code></div>
                                    <strong class="{{ $setting['status'] === 'Disabled' ? 'missing' : '' }}">{{ $setting['status'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(count($integration['credentials']))
                        <h3 class="api-section-title">Required credentials</h3>
                        <div class="api-credentials">
                            @foreach($integration['credentials'] as $credential)
                                <div class="api-credential">
                                    <div><small>{{ $credential['type'] }}</small><code>{{ $credential['name'] }}</code></div>
                                    <strong class="{{ $credential['status'] === 'Missing' ? 'missing' : '' }}">{{ $credential['status'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach

            <div class="api-security-note">
                Secret values are never displayed here. Configure credentials in the server environment and refresh Laravel's cached configuration after changing them.
            </div>
        </div>
    </main>
</body>
</html>
