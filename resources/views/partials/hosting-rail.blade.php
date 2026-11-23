{{--
    The floating switcher on the left of every public hosting page.

    @include('partials.hosting-rail', ['railRegion' => 'local'|'usa'|'wordpress', 'railActive' => 'shared'|'vps'|'dedicated'|'wordpress'])

    Local / USA pages : Shared · VPS · Dedicated of THAT region, then two jumps
                        (the other region, and WordPress).
    WordPress page    : Local · USA · WordPress.
    Design and behaviour (show after the banner, hide under 1180px) live in
    public/css/hosting-nav.css and the scroll script at the bottom of each page.
--}}
@php
    $railRegion = $railRegion ?? 'local';
    $railActive = $railActive ?? 'shared';
    $railIcons = [
        'shared' => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>',
        'vps' => '<rect x="3" y="4" width="18" height="6.5" rx="2"/><rect x="3" y="13.5" width="18" height="6.5" rx="2"/><path d="M7 7.2h.01M7 16.7h.01"/>',
        'dedicated' => '<rect x="7" y="7" width="10" height="10" rx="2"/><rect x="10.5" y="10.5" width="3" height="3"/><path d="M9 3v3M15 3v3M9 18v3M15 18v3M3 9h3M3 15h3M18 9h3M18 15h3"/>',
        'local' => '<path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'usa' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 4 5.6 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.6-4-9s1.4-6.4 4-9z"/>',
        'wordpress' => '<circle cx="12" cy="12" r="9"/><path d="M7.5 9l2.5 7 2-5.5L14 16l2.5-7"/>',
    ];
    $railItems = [];
    if ($railRegion === 'wordpress') {
        $railItems[] = ['label' => 'Local', 'icon' => 'local', 'url' => route('hosting.region', ['region' => 'local', 'type' => 'shared']), 'active' => false];
        $railItems[] = ['label' => 'USA', 'icon' => 'usa', 'url' => route('hosting.region', ['region' => 'usa', 'type' => 'shared']), 'active' => false];
        $railItems[] = ['label' => 'WordPress', 'icon' => 'wordpress', 'url' => route('hosting.wordpress'), 'active' => true];
    } else {
        $otherRegion = $railRegion === 'usa' ? 'local' : 'usa';
        foreach (['shared' => 'Shared', 'vps' => 'VPS', 'dedicated' => 'Dedicated'] as $railType => $railLabel) {
            $railItems[] = ['label' => $railLabel, 'icon' => $railType, 'url' => route('hosting.region', ['region' => $railRegion, 'type' => $railType]), 'active' => $railActive === $railType];
        }
        $railItems[] = ['label' => $otherRegion === 'usa' ? 'USA' : 'Local', 'icon' => $otherRegion, 'url' => route('hosting.region', ['region' => $otherRegion, 'type' => $railActive]), 'active' => false, 'divider' => true];
        $railItems[] = ['label' => 'WordPress', 'icon' => 'wordpress', 'url' => route('hosting.wordpress'), 'active' => false];
    }
@endphp
<nav class="hosting-rail" aria-label="Switch hosting type" data-region="{{ $railRegion }}">
@foreach($railItems as $railItem)
    <a href="{{ $railItem['url'] }}"@if(!empty($railItem['divider'])) class="rail-divider{{ $railItem['active'] ? ' is-active' : '' }}"@elseif($railItem['active']) class="is-active"@endif @if($railItem['active']) aria-current="page"@endif><svg class="di-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $railIcons[$railItem['icon']] !!}</svg><strong>{{ $railItem['label'] }}</strong></a>
@endforeach
</nav>
