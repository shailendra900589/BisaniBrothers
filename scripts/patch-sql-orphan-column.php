<?php
$f = dirname(__DIR__) . '/bisanibrothers_2026.sql';
$c = file_get_contents($f);
$c = preg_replace("/'(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})', 1\);/", "'$1', 1, 0);", $c);
file_put_contents($f, $c);
echo "Patched blog INSERT rows with is_orphan=0.\n";
