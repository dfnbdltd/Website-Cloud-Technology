<?php
<?php
/**
 * Throwaway: print the one-line assertion summary at the end of each PHPUnit
 * failure block ("' contains "X"." / "' does not see "X"."), skipping the HTML
 * dump that makes the raw output unreadable.
 */
$path = dirname(__DIR__) . '/.sleepycode/fv4.txt';
$lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($path)));

foreach ($lines as $line) {
    $t = rtrim($line);
    if ($t === '') {
        continue;
    }
    // The assertion summary is the short trailing line of a dump.
    if (strlen($t) < 160 && (strpos($t, "' contains ") !== false || strpos($t, "' does not see ") !== false || strpos($t, 'is identical to') !== false)) {
        echo $t . PHP_EOL;
    }
}
