{{-- One brand mark on every page: the same cloud favicon the home page uses.
     This is the single source of truth for the site icon. Include it from
     partials/head and from any standalone page that renders its own <head>
     (the hosting pages) so the mark never drifts.

     - favicon.ico  : 16/32/48/64px frames for every legacy engine & bookmark
     - favicon.svg  : crisp vector on HiDPI displays and Safari pinned tabs
     - favicon-*.png: apple-touch-icon (iOS ignores SVG for that slot) --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48 64x64">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon-180.png') }}">
<link rel="mask-icon" href="{{ asset('favicon.svg') }}" color="#2563eb">
