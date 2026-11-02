{{--
    Section page header: back link, kicker, title, blurb, count and the
    section's call to action.

    Data: $section, $count, $countLabel ('domain' / 'item' / 'invoice' ...)
--}}
<div class="store-page-head">
    <div class="store-page-head-copy">
        <a class="store-back" href="{{ route('mystore') }}">← All Items</a>
        <span class="domain-kicker">{{ $section['kicker'] }}</span>
        <h1 class="workspace-title">{{ $section['title'] }}</h1>
        <p class="workspace-subtitle">{{ $section['blurb'] }}</p>
    </div>
    <div class="store-page-head-aside">
        <span class="store-count-pill">{{ $count }} {{ $count === 1 ? $countLabel : $countLabel . 's' }}</span>
        @if(!empty($section['cta']['route']))
            <a class="store-primary" href="{{ route($section['cta']['route'], $section['cta']['params']) }}">
                {{ $section['cta']['label'] }} <b aria-hidden="true">→</b>
            </a>
        @endif
    </div>
</div>
