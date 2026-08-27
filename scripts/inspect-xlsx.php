<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? '';
$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();

for ($row = 1; $row <= min(25, $sheet->getHighestRow()); $row++) {
    $parts = [];
    foreach (range('A', 'H') as $col) {
        $val = $sheet->getCell($col.$row)->getValue();
        if ($val !== null && $val !== '') {
            $parts[] = $col.':'.(is_string($val) ? substr($val, 0, 60) : $val);
        }
    }
    if ($parts !== []) {
        echo "R{$row} | ".implode(' | ', $parts).PHP_EOL;
    }
}

echo 'Highest row: '.$sheet->getHighestRow().PHP_EOL;
echo 'Merged: '.json_encode($sheet->getMergeCells()).PHP_EOL;
