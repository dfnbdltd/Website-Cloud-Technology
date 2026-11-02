{{--
    Shared branded loading spinner (site-spinner).
    Brand palette: navy #17365e · accent #2168c1 · light blues #6ea8ff/#cfe4ff.
    Fullscreen overlay with animated 0-100% counter, "Searching…" / "Loading…"
    states and a progress bar. Loaded once via partials/head on every page, so
    any form can opt in with a data-spinner attribute:

        <form method="POST" action="…" data-spinner="search">…</form>

    Programmatic use (AJAX / fetch):
        SiteSpinner.show({ title: 'Loading…' });
        SiteSpinner.progress(42);          // manual 0-100 value
        SiteSpinner.finish();              // animate to 100%, then fade out
    CSS: public/css/site-spinner.css · JS: public/js/site-spinner.js
--}}
@php($sspinCss = @filemtime(public_path('css/site-spinner.css')))
@php($sspinJs = @filemtime(public_path('js/site-spinner.js')))
<link rel="stylesheet" href="{{ asset('css/site-spinner.css') }}@if($sspinCss)?v={{ $sspinCss }}@endif">
<script src="{{ asset('js/site-spinner.js') }}@if($sspinJs)?v={{ $sspinJs }}@endif" defer></script>
