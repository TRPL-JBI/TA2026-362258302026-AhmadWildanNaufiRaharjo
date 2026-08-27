<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$path = $argv[1] ?? '';
$sheet = IOFactory::load($path)->getActiveSheet();

foreach ([4, 5, 6, 7, 22, 23, 24] as $row) {
    $style = $sheet->getStyle('A'.$row.':G'.$row);
    $bg = $style->getFill()->getStartColor()->getRGB();
    $b = $sheet->getCell('B'.$row)->getValue();
    $c = substr((string) $sheet->getCell('C'.$row)->getValue(), 0, 40);
    echo "R{$row} B={$b} C={$c} bg={$bg}".PHP_EOL;
}

// count rows with criteria numbers in B
$count = 0;
for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
    $b = (string) $sheet->getCell('B'.$row)->getValue();
    if (preg_match('/^\d+\.\d+\.\d+$/', $b)) {
        $count++;
    }
}
echo "Leaf criteria rows: {$count}".PHP_EOL;
