<?php
/**
 * Search bar fix-up, round 2:
 *   1. Add a magnifying-glass SVG to the homepage button (it had an empty
 *      span whose CSS lives only in domains.css, so nothing rendered).
 *   2. Drop the trailing "." from "Search domain names." on both bars.
 *   3. Recolour the button from green to the site's own blue so it matches
 *      the rest of the website (buttons use --blue #2563eb / #1d4ed8).
 * Appends to CSS files; normalises/edits/restores CRLF for the views.
 */

function apply_edit($path, array $replacements)
{
    $raw = file_get_contents($path);
    $hadCrlf = strpos($raw, "\r\n") !== false;
    $text = $hadCrlf ? str_replace("\r\n", "\n", $raw) : $raw;

    foreach ($replacements as $old => $new) {
        if (strpos($text, $old) === false) {
            fwrite(STDERR, "MISS in $path: " . substr($old, 0, 90) . "\n");
            exit(1);
        }
        $text = str_replace($old, $new, $text);
    }

    if ($hadCrlf) {
        $text = str_replace("\n", "\r\n", $text);
    }
    file_put_contents($path, $text);
    echo "OK: $path\n";
}

/* 1 + 2. home.blade.php: icon inside the button, text without the period */
apply_edit('resources/views/home.blade.php', [
    '<button type="submit"><span class="search-button-icon" aria-hidden="true"></span> Search domain names.</button>'
        => '<button type="submit"><span class="search-button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 2a8 8 0 105.29 14.29l4.71 4.7 1.41-1.41-4.7-4.71A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg></span>Search domain names</button>',
]);

/* 2. domains/index.blade.php: drop the period (icon markup already in place) */
apply_edit('resources/views/domains/index.blade.php', [
    '<button type="submit">Search domain names.'
        => '<button type="submit">Search domain names',
]);

/* 3. home.css: site-blue button + inline-SVG icon styling (the old green
      block is still in the file tail; these rules come after and win). */
$homeCss = <<<'CSS'

/* Search button matches the site blue; magnifier icon is an inline SVG. */
.site-search button{
    gap:9px;
    background:var(--blue);
    box-shadow:0 8px 20px rgba(37,99,235,.28);
}
.site-search button:hover{
    background:var(--blue-2);
}
.site-search .search-button-icon{
    display:inline-flex;
    width:18px;
    height:18px;
    margin:0;
    font-size:0;
    line-height:0;
}
.site-search .search-button-icon svg{
    width:18px;
    height:18px;
    fill:#fff;
}
CSS;
file_put_contents('public/css/home.css', $homeCss, FILE_APPEND);
echo "OK: public/css/home.css\n";

/* 3. domains.css: same blue for the client/guest primary bar (overrides
      the green appended earlier by order), plus SVG-safe icon sizing. */
$domainsCss = <<<'CSS'

/* Search button matches the site blue (supersedes the green above). */
.domain-search-primary .domain-search-row button{
    background:var(--blue);
    box-shadow:0 10px 22px rgba(60,127,240,.24);
}
.domain-search-primary .domain-search-row button:hover{
    background:#286edc;
    box-shadow:0 12px 26px rgba(60,127,240,.3);
}
.domain-search-primary .domain-search-row button .search-button-icon svg{
    width:18px;
    height:18px;
    fill:#fff;
}
CSS;
file_put_contents('public/css/domains.css', $domainsCss, FILE_APPEND);
echo "OK: public/css/domains.css\n";

echo "DONE\n";
