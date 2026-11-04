<?php
/**
 * Consolidate the search-bar CSS appended over the last two rounds:
 * remove the dead green rules so only the final (blue, icon-aware) block
 * remains. Pure textual surgery on the two CSS files.
 */

$homeGreen = <<<'BLOCK'
/* ---------------------------------------------------------------------------
   Domain search bar �?" reference look: one big rounded pill, large font,
   green "Search domain names." button on the right.
   ------------------------------------------------------------------------- */
.site-search{
    max-width:680px;
    padding:8px;
    border-radius:999px;
    border-color:#dbe3ee;
    box-shadow:0 18px 44px rgba(15,23,42,.10);
}
.site-search input{
    padding:14px 10px 14px 22px;
    font-size:18px;
}
.site-search input::placeholder{
    color:#9aa7b8;
}
.site-search button{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:15px 28px;
    border-radius:999px;
    background:#16a34a;
    font-size:16px;
}
.site-search button:hover{
    background:#15803d;
}
@media(max-width:560px){
    .site-search{padding:7px}
    .site-search input{padding:12px 8px 12px 16px;font-size:16px}
    .site-search button{padding:13px 18px;font-size:15px}
}
BLOCK;

$homeBlue = <<<'BLOCK'
/* ---------------------------------------------------------------------------
   Domain search bar — reference look: one big rounded pill, large font,
   "Search domain names." button on the right (site blue + magnifier icon).
   ------------------------------------------------------------------------- */
.site-search{
    max-width:680px;
    padding:8px;
    border-radius:999px;
    border-color:#dbe3ee;
    box-shadow:0 18px 44px rgba(15,23,42,.10);
}
.site-search input{
    padding:14px 10px 14px 22px;
    font-size:18px;
}
.site-search input::placeholder{
    color:#9aa7b8;
}
.site-search button{
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:15px 28px;
    border-radius:999px;
    background:var(--blue);
    font-size:16px;
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
@media(max-width:560px){
    .site-search{padding:7px}
    .site-search input{padding:12px 8px 12px 16px;font-size:16px}
    .site-search button{padding:13px 18px;font-size:15px}
}
BLOCK;

$homePath = 'public/css/home.css';
$home = file_get_contents($homePath);
if (strpos($home, 'background:#16a34a') === false) {
    echo "home.css: no green rules found (already clean)\n";
} else {
    // The round-1 block starts at its comment and ends before the round-2 comment.
    $start = strpos($home, '/* ---');
    $start = strpos($home, '/* ---', $start === false ? 0 : $start); // first comment is elsewhere; find the search one
    $marker = '/* Search button matches the site blue';
    $mid = strpos($home, $marker);
    if ($mid === false) {
        fwrite(STDERR, "MISS: round-2 marker in home.css\n");
        exit(1);
    }
    // The round-1 block begins at the LAST '/* ----' before the marker.
    $start = strrpos(substr($home, 0, $mid), '/* ----');
    // Round-2 block ends at the final media query of the file tail.
    $end = strrpos($home, '}'); // last byte of file is the closing brace
    $before = substr($home, 0, $start);
    $newTail = $homeBlue . "\n";
    file_put_contents($homePath, $before . $newTail);
    echo "OK: home.css consolidated (" . (strlen($home) - strlen($before . $newTail)) . " bytes removed)\n";
}

/* domains.css: the green rules for the primary bar sit in the round-1 block;
   replace the whole appended region with one clean blue block. */
$domainsPath = 'public/css/domains.css';
$domains = file_get_contents($domainsPath);
$marker = '/* ---------------------------------------------------------------------------';
$pos = strpos($domains, "   Domain search bar");
if ($pos === false) {
    echo "domains.css: search-bar block not found (already clean?)\n";
} else {
    $start = strrpos(substr($domains, 0, $pos), '/* ---');
    $newTail = <<<'BLOCK'
/* ---------------------------------------------------------------------------
   Domain search bar — reference look (client-area page): a big rounded pill
   with a large input and a "Search domain names." button on the right,
   coloured with the site blue and carrying the magnifier icon.
   Comes after every earlier rule, so the new shape + sizes win by order.
   ------------------------------------------------------------------------- */
.domain-search-primary{padding:22px 24px 18px;border-radius:20px}
.domain-search-primary label{color:#17365e;font-size:17px}
.domain-search-primary .domain-search-row{
    max-width:100%;
    min-height:64px;
    margin-top:12px;
    border-radius:999px;
    background:#fff;
    box-shadow:0 14px 34px rgba(30,73,117,.10);
}
.domain-search-primary .domain-search-row>span{padding-left:22px;font-size:22px}
.domain-search-primary .domain-search-row input{
    padding:16px 14px;
    color:#17365e;
    font-size:18px;
}
.domain-search-primary .domain-search-row input::placeholder{
    color:#9fb0c2;
}
.domain-search-primary .domain-search-row button{
    align-self:center;
    gap:10px;
    min-width:200px;
    min-height:52px;
    margin:6px 6px 6px 10px;
    padding:0 26px;
    border-radius:999px;
    background:var(--blue);
    font-size:15px;
    font-weight:800;
    box-shadow:0 10px 22px rgba(60,127,240,.24);
}
.domain-search-primary .domain-search-row button:hover{
    background:#286edc;
    box-shadow:0 12px 26px rgba(60,127,240,.3);
}
.domain-search-primary .domain-search-row button b{margin-left:0;font-size:18px}
.domain-search-primary .domain-search-row button .search-button-icon{
    position:relative;
    display:inline-block;
    flex:none;
    width:18px;
    height:18px;
    margin:0;
    color:#fff!important;
    font-size:0!important;
    line-height:0;
}
.domain-search-primary .domain-search-row button .search-button-icon svg{
    width:18px;
    height:18px;
    fill:#fff;
}
.domain-search-primary small{margin-top:14px;font-size:13px}
.domain-registration-guest .domain-search-primary .domain-search-row{
    max-width:760px;
    margin-left:auto;
    margin-right:auto;
}

/* Mobile: keep the pill readable; stack input above the full-width button */
@media(max-width:760px){
    .domain-search-primary{padding:19px 16px 16px}
    .domain-search-primary label{font-size:16px}
    .domain-search-primary .domain-search-row{min-height:0;border-radius:18px}
    .domain-search-primary .domain-search-row input{flex-basis:100%;min-height:52px;font-size:16px}
    .domain-search-primary .domain-search-row button{
        width:100%;
        min-width:0;
        min-height:48px;
        margin:6px;
        border-radius:999px;
        font-size:14px;
    }
}
BLOCK;
    file_put_contents($domainsPath, substr($domains, 0, $start) . $newTail . "\n");
    echo "OK: domains.css consolidated\n";
}

/* Final assertions */
$fail = 0;
foreach (['public/css/home.css', 'public/css/domains.css'] as $file) {
    $text = file_get_contents($file);
    foreach (['#16a34a', '#15803d'] as $green) {
        if (strpos($text, $green) !== false) {
            echo "FAIL: $file still contains $green\n";
            $fail++;
        }
    }
    $open = substr_count($text, '{');
    $close = substr_count($text, '}');
    echo "braces $file: open=$open close=$close " . ($open === $close ? 'BALANCED' : 'MISMATCH') . "\n";
    if ($open !== $close) { $fail++; }
}
echo $fail === 0 ? "CLEAN\n" : "$fail ISSUE(S)\n";
exit($fail === 0 ? 0 : 1);
