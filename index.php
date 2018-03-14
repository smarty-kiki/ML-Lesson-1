<?php

ini_set('memory_limit', '1024M');

include 'apriori_readable.php';

$record_str = file_get_contents('records_50.csv');

$record_lines = explode("\n", $record_str); unset($record_str);

$records = [];
foreach ($record_lines as $line) {
    if (trim($line)) {
        $records[] = explode(',', $line);
    }
} unset($record_lines);

$res = apriori($records, 4, 0.006, 0.3);

echo "Supports\n";
echo print_supports($res['supports']);
echo "\n\nConfidiences\n";
echo print_confidiences($res['confidiences']);
