{{--
    Shared branded loading spinner (site-spinner).
    Fullscreen dark overlay containing a single dot loader — no card, no
    counter, no progress bar. Loaded once via partials/head on every page, so
    any form can opt in with a data-spinner attribute:

        <form method="POST" action="…" data-spinner="search">…</form>

    Programmatic use (AJAX / fetch):
        SiteSpinner.show({ title: 'Loading…' });
        SiteSpinner.progress(42);          // tracks progress internally
        SiteSpinner.finish();              // animate to 100%, then fade out
    CSS: public/css/site-spinner.css · JS: public/js/site-spinner.js
--}}
@php($sspinCss = @filemtime(public_path('css/site-spinner.css')))
@php($sspinJs = @filemtime(public_path('js/site-spinner.js')))
<link rel="stylesheet" href="{{ asset('css/site-spinner.css') }}@if($sspinCss)?v={{ $sspinCss }}@endif">
<script src="{{ asset('js/site-spinner.js') }}@if($sspinJs)?v={{ $sspinJs }}@endif" defer></script>
