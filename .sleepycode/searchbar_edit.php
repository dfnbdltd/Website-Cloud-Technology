<?php
/**
 * One-shot edit: restyle the two domain search bars (home + domain page)
 * to the reference look — one big rounded pill, larger font, green button
 * on the right — and make the fonts bigger.
 *
 * - home.blade.php: placeholder -> "Search domain names." + submit label
 * - domains/index.blade.php: placeholder -> "Search domain names."
 * - public/css/home.css: append .site-search overrides (pill + green button)
 * - public/css/domains.css: append .domain-search-primary overrides
 *   (big pill, bigger font, green button) + mobile tweaks
 *
 * CRLF-safe: normalises to LF, edits, restores CRLF, verifies.
 */

function apply_edit($path, array $replacements)
{
    $raw = file_get_contents($path);
    $hadCrlf = strpos($raw, "\r\n") !== false;
    $text = $hadCrlf ? str_replace("\r\n", "\n", $raw) : $raw;

    foreach ($replacements as $old => $new) {
        if (strpos($text, $old) === false) {
            fwrite(STDERR, "MISS in $path: " . substr($old, 0, 80) . "\n");
            exit(1);
        }
        $text = str_replace($old, $new, $text);
    }

    if ($hadCrlf) {
        $text = str_replace("\n", "\r\n", $text);
    }
    file_put_contents($path, $text);
    echo "OK: $path (crlf=" . var_export($hadCrlf, true) . ")\n";
}

/* 1. home.blade.php — new placeholder + "Search domain names." submit text */
apply_edit('resources/views/home.blade.php', [
    '<input name="domain" placeholder="Find your perfect domain — e.g. yourbrand.com" required><button type="submit"><span class="search-button-icon" aria-hidden="true"></span> Search domain</button>'
        => '<input name="domain" placeholder="Search domain names." required><button type="submit"><span class="search-button-icon" aria-hidden="true"></span> Search domain names.</button>',
]);

/* 2. domains/index.blade.php — new placeholder only */
apply_edit('resources/views/domains/index.blade.php', [
    'placeholder="yourbrand.com" autocomplete="off" required><button type="submit">Search domain'
        => 'placeholder="Search domain names." autocomplete="off" required><button type="submit">Search domain names.',
]);

/* 3. home.css — append the reference search-bar styling */
$homeCss = <<<'CSS'

/* ---------------------------------------------------------------------------
   Domain search bar — reference look: one big rounded pill, large font,
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
CSS;
file_put_contents('public/css/home.css', $homeCss, FILE_APPEND);
echo "OK: public/css/home.css (appended " . strlen($homeCss) . " bytes)\n";

/* 4. domains.css — bigger search bar on the domain page, green button */
$domainsCss = <<<'CSS'

/* ---------------------------------------------------------------------------
   Domain search bar — reference look (client-area page): a big rounded pill
   with a large input and a green "Search domain names." button on the right.
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
    background:#16a34a;
    font-size:15px;
    font-weight:800;
    box-shadow:0 10px 22px rgba(22,163,74,.28);
}
.domain-search-primary .domain-search-row button:hover{
    background:#15803d;
    box-shadow:0 14px 28px rgba(22,163,74,.32);
}
.domain-search-primary .domain-search-row button b{margin-left:0;font-size:18px}
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
CSS;
file_put_contents('public/css/domains.css', $domainsCss, FILE_APPEND);
echo "OK: public/css/domains.css (appended " . strlen($domainsCss) . " bytes)\n";

echo "DONE\n";
