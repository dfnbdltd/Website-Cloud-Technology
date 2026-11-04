<?php
/**
 * Search bar fix-up, round 3 (final):
 *   1. domains/index.blade.php: replace the old pseudo-element icon span with
 *      the same inline magnifier SVG used on the homepage.
 *   2. Remove the trailing "." from the placeholder on BOTH bars
 *      ("Search domain names." -> "Search domain names").
 *   3. domains.css: suppress the old CSS pseudo-element magnifier ONLY inside
 *      the primary search bar's button (the admin invoices search button
 *      still relies on the pseudo-element icon, so the override is scoped).
 * CRLF-safe edit + append, with verification.
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

/* 1 + 2. domains page: SVG icon in the button, period dropped everywhere */
apply_edit('resources/views/domains/index.blade.php', [
    'placeholder="Search domain names." autocomplete="off" required><button type="submit">Search domain names
                        <span class="search-button-icon" aria-hidden="true"></span></button>'
        => 'placeholder="Search domain names" autocomplete="off" required><button type="submit"><span class="search-button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 2a8 8 0 105.29 14.29l4.71 4.7 1.41-1.41-4.7-4.71A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg></span>Search domain names</button>',
]);

/* 2. homepage: period dropped from the placeholder */
apply_edit('resources/views/home.blade.php', [
    'placeholder="Search domain names." required>'
        => 'placeholder="Search domain names" required>',
]);

/* 3. domains.css: hide the old pseudo-element magnifier inside the primary
      bar's button only (the SVG replaces it; admin search keeps its icon). */
$css = implode("\n", [
    '',
    '/* The primary bar\'s button icon is an inline SVG now: retire the old',
    '   CSS pseudo-element magnifier in this scope only (other .search-button-icon',
    '   usages, e.g. the admin invoice search, keep the pseudo-element version). */',
    '.domain-search-primary .domain-search-row button .search-button-icon::before,',
    '.domain-search-primary .domain-search-row button .search-button-icon::after{',
    '    display:none;',
    '}',
]);
file_put_contents('public/css/domains.css', $css, FILE_APPEND);
echo "OK: public/css/domains.css\n";

/* Verification */
$fail = 0;
$checks = [
    'resources/views/home.blade.php' => [
        'placeholder="Search domain names" required>' => 'placeholder without period',
        'search-button-icon' => 'icon present',
        '<svg viewBox="0 0 24 24"' => 'SVG magnifier present',
    ],
    'resources/views/domains/index.blade.php' => [
        'placeholder="Search domain names" autocomplete="off" required>' => 'placeholder without period',
        '<svg viewBox="0 0 24 24"' => 'SVG magnifier present in button',
        '</span>Search domain names</button>' => 'button text follows icon, no period',
    ],
    'public/css/domains.css' => [
        '.domain-search-primary .domain-search-row button .search-button-icon::before' => 'pseudo-element suppression present',
    ],
];
foreach ($checks as $file => $rules) {
    $text = file_get_contents($file);
    foreach ($rules as $needle => $why) {
        if (strpos($text, $needle) === false) {
            echo "FAIL: $file missing [$needle] ($why)\n";
            $fail++;
        } else {
            echo "ok  : $file has [$why]\n";
        }
    }
}

/* No stray "Search domain names." with period should remain in either view */
foreach (['resources/views/home.blade.php', 'resources/views/domains/index.blade.php'] as $file) {
    $text = file_get_contents($file);
    if (preg_match('/Search domain names\./', $text)) {
        echo "FAIL: $file still contains 'Search domain names.' with period\n";
        $fail++;
    } else {
        echo "ok  : $file has no trailing period\n";
    }
}

/* CSS brace balance */
foreach (['public/css/home.css', 'public/css/domains.css'] as $file) {
    $text = file_get_contents($file);
    $open = substr_count($text, '{');
    $close = substr_count($text, '}');
    echo "braces $file: open=$open close=$close " . ($open === $close ? 'BALANCED' : 'MISMATCH') . "\n";
    if ($open !== $close) { $fail++; }
}

/* Blade compile */
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    $app = require 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $compiler = $app->make('view')->getEngineResolver()->resolve('blade')->getCompiler();
    foreach (['resources/views/home.blade.php', 'resources/views/domains/index.blade.php'] as $view) {
        try {
            $compiler->compile($view);
            echo "compiled: $view\n";
        } catch (Throwable $e) {
            echo "COMPILE FAIL: $view — " . $e->getMessage() . "\n";
            $fail++;
        }
    }
}

echo $fail === 0 ? "ALL CHECKS PASSED\n" : "$fail CHECK(S) FAILED\n";
exit($fail === 0 ? 0 : 1);
