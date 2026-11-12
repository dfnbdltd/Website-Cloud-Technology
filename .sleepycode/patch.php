<?php
/**
 * CRLF-safe exact-text replacement for this repo.
 *
 * Usage: php .sleepycode/patch.php <spec.json>
 * spec = [{ "file": "...", "old": "...", "new": "..." }, ...]
 *
 * Files here are stored with CRLF endings, so an LF write would produce a
 * whole-file diff. This normalises to LF, applies an exact single-occurrence
 * replacement, then restores CRLF.
 */
$specPath = $argv[1] ?? null;
if ($specPath === null || !is_file($specPath)) {
    fwrite(STDERR, "usage: php patch.php <spec.json>\n");
    exit(2);
}

$spec = json_decode(file_get_contents($specPath), true);
if (!is_array($spec)) {
    fwrite(STDERR, "spec is not valid JSON: " . json_last_error_msg() . "\n");
    exit(2);
}

$root = dirname(__DIR__);
$failures = 0;

foreach ($spec as $index => $patch) {
    $path = $root . '/' . ltrim($patch['file'], '/\\');
    $label = '[' . $index . '] ' . $patch['file'];

    if (!is_file($path)) {
        fwrite(STDERR, $label . "  MISSING FILE\n");
        $failures++;
        continue;
    }

    $source = file_get_contents($path);
    $hadCrlf = strpos($source, "\r\n") !== false;
    $text = $hadCrlf ? str_replace("\r\n", "\n", $source) : $source;

    $occurrences = substr_count($text, $patch['old']);
    if ($occurrences !== 1) {
        fwrite(STDERR, $label . "  EXPECTED 1 MATCH, FOUND {$occurrences}\n");
        $failures++;
        continue;
    }

    $text = str_replace($patch['old'], $patch['new'], $text);
    $output = $hadCrlf ? str_replace("\n", "\r\n", $text) : $text;
    file_put_contents($path, $output);

    echo $label . "  OK  (" . strlen($source) . ' -> ' . strlen($output) . " bytes)\n";
}

exit($failures === 0 ? 0 : 1);
