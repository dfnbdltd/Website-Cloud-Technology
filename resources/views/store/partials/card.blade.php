{{--
    One All Items card shell.

    Data:
      $serial  1-7 position, shown as the "01".."07" chip
      $section one entry from App\Support\StoreInventory::sections()
      $count   how many rows the section holds
      $summary one-line description of the contents (null when empty)
      $slot    the rows, rendered only when $count > 0
--}}
@php($viewAll = route($section['url'][0], $section['url'][1] ?? []))
<article class="store-card tone-{{ $section['tone'] }}" id="card-{{ $section['key'] }}">
    <span class="store-card-serial" aria-hidden="true">{{ sprintf('%02d', $serial) }}</span>

    <div class="store-card-head">
        <span class="store-card-icon" aria-hidden="true">{{ $section['icon'] }}</span>
        <div class="store-card-heading">
            <span class="domain-kicker">{{ $section['kicker'] }}</span>
            <h2>{{ $section['title'] }}</h2>
        </div>
        @if($count > 0)
            <b class="store-card-count" title="{{ $count }} in this section">{{ $count }}</b>
        @endif
    </div>

    <p class="store-card-summary">{{ $summary ?: $section['blurb'] }}</p>

    @if($count > 0)
        <div class="store-card-rows">{{ $slot }}</div>
    @else
        <div class="store-card-empty">
            <p>{{ $section['empty_short'] }}</p>
            <small>{{ $section['blurb'] }}</small>
        </div>
    @endif

    <a class="store-card-link" href="{{ $viewAll }}">
        {{ $count > 0 ? 'View details' : 'Open section' }} <b aria-hidden="true">→</b>
    </a>
</article>
