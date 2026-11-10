{{--
    Client-area service page.

    `domain-transfer` renders the same interactive transfer experience as the
    public page, inside the normal client shell (sidebar + topbar). Every other
    slug keeps the minimal "one icon + one line" screen.

    Data: $service (config/coming_soon.php), $navActive (sidebar active key).
--}}
@php($isTransfer = ($slug ?? '') === 'domain-transfer')
@php($isSsl = ($slug ?? '') === 'ssl-certificate')
@php($transferCssVersion = @md5_file(public_path('css/domain-transfer.css')))
@php($transferJsVersion = @md5_file(public_path('js/domain-transfer.js')))
@php($sslCssVersion = @md5_file(public_path('css/ssl-plans.css')))
@php($sslPlanService = app(\App\Services\SslPlanService::class))

@extends('store.layout', [
    'pageTitle' => $service['title'].($isTransfer ? ' — Transfer your domain' : ' — Coming soon'),
    'navActive' => $navActive,
    'topbarTitle' => $isTransfer ? 'Domain Transfer' : ($isSsl ? 'SSL Certificate' : 'Coming soon'),
])

@if($isTransfer)
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/domain-transfer.css') }}?v={{ $transferCssVersion }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/domain-transfer.js') }}?v={{ $transferJsVersion }}" defer></script>
    @endpush
@endif

@if($isSsl)
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/ssl-plans.css') }}?v={{ $sslCssVersion }}">
    @endpush
@endif

@section('store')
    @if($isTransfer)
        <div class="dt-page dt-page--client">
            @include('partials.domain-transfer', ['dtVariant' => 'client'])
        </div>
    @elseif($isSsl)
        @include('partials.ssl-plans', ['sslVariant' => 'client', 'sslPlans' => $sslPlanService->plans()])
    @else
        <div class="store-coming tone-{{ $service['tone'] }}">
            <span class="store-coming-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                    <path d="{{ $service['icon'] }}"/>
                </svg>
            </span>

            <h1 class="store-coming-title">{{ $service['title'] }} is coming soon</h1>
        </div>
    @endif
@endsection
