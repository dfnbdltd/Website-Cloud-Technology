<?php
/* Match the card head/foot inset (24px) so the labels line up with the domain
   name and the footer links. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "  gap:0 8px;\n  padding:15px 22px 16px;\n}";
$new = "  gap:0 8px;\n  padding:15px 24px 16px;\n}";
if (strpos($s, $old) === false) {
    fwrite(STDERR, "padding rule not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "inset aligned\n";
