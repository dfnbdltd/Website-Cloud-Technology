<?php
/* Contrast pass on the inline fact strip: the label and the connective "on"
   were too light (2.6:1 / 1.95:1). Both now clear 4.5:1 on the card cell,
   differentiated by weight and case instead of by fading out. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$pairs = [
    "  color:#8ea2b9;\n  font-size:10.5px;\n  font-weight:800;\n  letter-spacing:1.15px;"
        => "  color:#5f768c;\n  font-size:10.5px;\n  font-weight:800;\n  letter-spacing:1.15px;",
    "  color:#a9bccf;\n  font:600 12.5px 'DM Sans';\n  letter-spacing:.1px;"
        => "  color:#63798f;\n  font:500 13px 'DM Sans';\n  letter-spacing:.1px;",
];

foreach ($pairs as $old => $new) {
    if (strpos($s, $old) === false) {
        fwrite(STDERR, "missing: " . substr($old, 0, 32) . "\n");
        exit(1);
    }
    $s = str_replace($old, $new, $s);
}

file_put_contents($path, str_replace("\n", "\r\n", $s));
echo "contrast fixed\n";
