{{--
    Empty state shared by all section pages.

    Data: $section
--}}
<div class="store-empty tone-{{ $section['tone'] }}">
    <span class="store-empty-icon" aria-hidden="true">{{ $section['icon'] }}</span>
    <h2>{{ $section['empty_short'] }}</h2>
    <p>{{ $section['empty'] }}</p>
    @if(!empty($section['cta']['route']))
        <a class="store-primary" href="{{ route($section['cta']['route'], $section['cta']['params']) }}">
            {{ $section['cta']['label'] }} <b aria-hidden="true">→</b>
        </a>
    @else
        <button class="store-ghost-link coming-soon-trigger" type="button" data-service="{{ $section['title'] }}">Tell me when it launches</button>
    @endif
</div>
