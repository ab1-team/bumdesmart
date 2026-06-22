<?php
$data = [
    ['ID', 'Name', 'Email'],
    [1, 'John Doe', 'john@example.com'],
    [2, 'Jane Smith', 'jane@example.com']
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
if ($output === false) {
    exit('Tidak bisa membuka output stream');
}

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

foreach ($data as $row) {
    fputcsv($output, $row, ';', '"');
}

fclose($output);
exit;
