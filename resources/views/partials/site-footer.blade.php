<footer class="site-footer">
    <div class="site-wrap site-footer-inner">
        <a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a>
        <span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span>
        <span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span>
        <a href="{{ route('admin.login') }}">Admin login</a>
    </div>
</footer>
