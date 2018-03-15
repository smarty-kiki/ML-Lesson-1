<?php

ini_set('memory_limit', '1024M');
$start = microtime(true);

//include 'apriori_readable.php';
include 'apriori_speed.php';

//$record_str = file_get_contents('records_50.csv');
$record_str = file_get_contents('records.csv');

$record_lines = explode("\n", $record_str); unset($record_str);

$records = [];
foreach ($record_lines as $line) {

    if (trim($line)) {

        $record = explode(',', $line);

        sort($record);

        $records[] = $record;
    }

} unset ($record_lines);

$res = apriori($records, 4, 0.006, 0.3);

echo "Supports\n";
echo print_supports($res['supports']);

echo "\n\nConfidiences\n";
echo print_confidiences($res['confidiences']);

echo "\n\nLifts\n";
echo print_lifts($res['lifts']);

$size = memory_get_usage(true);
echo "\n内存开销 ".round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.['b','kb','mb','gb','tb','pb'][$i];

echo "\n执行时间 ".(microtime(true) - $start)."s";
