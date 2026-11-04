<?php
/* Verify round-2 search bar edits: icon SVG, no trailing period, blue button. */

$checks = [
    'resources/views/home.blade.php' => [
        'Search domain names</button>' => 'button text has no period and follows the icon',
        'search-button-icon' => 'icon span present',
        '<svg viewBox="0 0 24 24"' => 'magnifier SVG present in the button',
        'Search domain names.' => false, // must be gone
    ],
    'resources/views/domains/index.blade.php' => [
        'Search domain names' => 'button text present',
        'search-button-icon' => 'icon span present',
        'Search domain names.' => false, // must be gone
    ],
    'public/css/home.css' => [
        '.site-search button{' => 'blue button override block present',
        'background:var(--blue)' => 'site blue used',
        '#16a34a' => false, // green should be superseded/not re-added
    ],
    'public/css/domains.css' => [
        '.domain-search-primary .domain-search-row button{' => 'button override present',
    ],
];

$fail = 0;
foreach ($checks as $file => $rules) {
    $text = file_get_contents($file);
    foreach ($rules as $needle => $why) {
        $found = strpos($text, $needle) !== false;
        if (is_bool($why)) {
            // negative check: needle must NOT be present
            if ($found) {
                echo "FAIL: $file still contains [$needle] (should be gone)\n";
                $fail++;
            } else {
                echo "ok  : $file no longer has [$needle]\n";
            }
        } else {
            if ($found) {
                echo "ok  : $file has [$needle]\n";
            } else {
                echo "FAIL: $file missing [$needle] ($why)\n";
                $fail++;
            }
        }
    }
}

/* The green hex must be gone from home.css (round 1 added it, round 2 replaced
   with blue — confirm the last block wins and green is absent). */
$home = file_get_contents('public/css/home.css');
if (strpos($home, '#16a34a') !== false) {
    echo "note: home.css still contains green #16a34a (superseded by later blue block)\n";
}

foreach (['public/css/home.css', 'public/css/domains.css'] as $file) {
    $text = file_get_contents($file);
    $open = substr_count($text, '{');
    $close = substr_count($text, '}');
    echo "braces $file: open=$open close=$close " . ($open === $close ? 'BALANCED' : 'MISMATCH') . "\n";
    if ($open !== $close) { $fail++; }
}

/* Blade compile through the framework */
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
