{{--
    Client-area "coming soon" page.

    Reached from the dashboard sidebar service rows (Domain Transfer, SSL
    Certificate, Business Email, SMS Service). It renders inside the normal
    client shell (sidebar + topbar) and shows only the service icon and one
    line: "<service> is coming soon".

    Data: $service (config/coming_soon.php), $navActive (sidebar active key).
--}}
@extends('store.layout', [
    'pageTitle' => $service['title'].' — Coming soon',
    'navActive' => $navActive,
    'topbarTitle' => 'Coming soon',
])

@section('store')
    <div class="store-coming tone-{{ $service['tone'] }}">
        <span class="store-coming-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="{{ $service['icon'] }}"/>
            </svg>
        </span>

        <h1 class="store-coming-title">{{ $service['title'] }} is coming soon</h1>
    </div>
@endsection
