<?php

namespace App\Services\Laporan;

use App\Models\LaporanGenerated;
use App\Models\User;
use App\Services\Pemantauan\IpamRekapReportDataService;
use App\Support\IpamBulan;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IpamRekapExcelBuilder
{
    private const TEMPLATE_PATH = 'templates/laporan/rekap-ipam-bulan.xlsx';

    private const HEADER_ROW = 4;

    private const DATA_START_ROW = 5;

    /**
     * @param  array<string, mixed>  $detail
     */
    public function build(array $detail, string $relativePath): void
    {
        $spreadsheet = IOFactory::load(resource_path(self::TEMPLATE_PATH));
        $sheet = $spreadsheet->getActiveSheet();

        $bulanLabel = strtoupper((string) ($detail['bulan_label'] ?? ''));
        $tahun = (string) ($detail['tahun'] ?? '');

        $sheet->setCellValue('A2', sprintf('TABEL REKAP PER IPAM BULAN %s %s', $bulanLabel, $tahun));

        $this->resetBodyRows($sheet);

        $lastDataRow = $this->fillDetailRows($sheet, $detail);
        $lastRow = $this->writeRekapSections($sheet, $detail, $lastDataRow);

        $this->trimRowsAfter($sheet, $lastRow);

        $this->saveSpreadsheet($spreadsheet, $relativePath);
    }

    private function resetBodyRows(Worksheet $sheet): void
    {
        $highest = max($sheet->getHighestRow(), self::DATA_START_ROW);

        for ($row = self::DATA_START_ROW; $row <= $highest; $row++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $column) {
                $sheet->setCellValue($column.$row, null);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function fillDetailRows(Worksheet $sheet, array $detail): int
    {
        $row = self::DATA_START_ROW;

        /** @var list<array<string, mixed>> $units */
        $units = $detail['units'] ?? [];

        foreach ($units as $unit) {
            /** @var list<array<string, mixed>> $weeks */
            $weeks = $unit['weeks'] ?? [];

            foreach ($weeks as $week) {
                /** @var list<array<string, mixed>> $titikRows */
                $titikRows = $week['titik'] ?? [];

                foreach ($titikRows as $index => $titik) {
                    if ($index === 0) {
                        $sheet->setCellValue('A'.$row, (string) ($unit['nama_unit'] ?? '-'));
                        $sheet->setCellValue('B'.$row, (int) ($week['minggu_ke'] ?? 0));
                    }

                    $sheet->setCellValue('C'.$row, (string) ($titik['kode'] ?? '-'));
                    $sheet->setCellValue('D'.$row, (string) ($titik['lokasi'] ?? '-'));
                    $sheet->setCellValue('E'.$row, (string) ($titik['ph'] ?? '-'));
                    $sheet->setCellValue('F'.$row, (string) ($titik['alt'] ?? '-'));
                    $sheet->setCellValue('G'.$row, (string) ($titik['salmonella_display'] ?? $titik['salmonella'] ?? '-'));
                    $sheet->setCellValue('H'.$row, (string) ($titik['status'] ?? '-'));
                    $this->applyTableBorder($sheet, 'A', 'H', $row);
                    $row++;
                }
            }
        }

        return max(self::HEADER_ROW, $row - 1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function writeRekapSections(Worksheet $sheet, array $detail, int $lastDataRow): int
    {
        $row = $lastDataRow + 2;
        $bulanLabel = strtoupper((string) ($detail['bulan_label'] ?? ''));
        $tahun = (string) ($detail['tahun'] ?? '');

        $sheet->setCellValue('A'.$row, sprintf('REKAP KESELURUHAN BULAN %s %s', $bulanLabel, $tahun));
        $sheet->mergeCells('A'.$row.':F'.$row);
        $row += 2;

        $sheet->setCellValue('A'.$row, 'IPAM');
        $sheet->setCellValue('B'.$row, 'Minggu Sampling');
        $sheet->setCellValue('C'.$row, 'Jumlah Titik');
        $sheet->setCellValue('D'.$row, 'Hasil Baik');
        $sheet->setCellValue('E'.$row, 'Hasil Tidak Baik');
        $sheet->setCellValue('F'.$row, 'Catatan');
        $this->applyTableBorder($sheet, 'A', 'F', $row);
        $row++;

        /** @var list<array<string, mixed>> $units */
        $units = $detail['units'] ?? [];

        foreach ($units as $unit) {
            /** @var array<string, mixed> $rekap */
            $rekap = $unit['rekap'] ?? [];

            $sheet->setCellValue('A'.$row, (string) ($unit['nama_unit'] ?? '-'));
            $sheet->setCellValue('B'.$row, (string) ($rekap['minggu_sampling'] ?? '-'));
            $sheet->setCellValue('C'.$row, (int) ($rekap['jumlah_titik'] ?? 0));
            $sheet->setCellValue('D'.$row, (int) ($rekap['hasil_baik'] ?? 0));
            $sheet->setCellValue('E'.$row, (int) ($rekap['hasil_tidak_baik'] ?? 0));
            $sheet->setCellValue('F'.$row, (string) ($rekap['catatan'] ?? '-'));
            $this->applyTableBorder($sheet, 'A', 'F', $row);
            $row++;
        }

        $row++;

        $sheet->setCellValue('A'.$row, 'Parameter');
        $sheet->setCellValue('B'.$row, 'Hasil Rata - Rata');
        $this->applyTableBorder($sheet, 'A', 'B', $row);
        $row++;

        /** @var array<string, string> $parameter */
        $parameter = $detail['parameter'] ?? [];

        foreach ([
            ['Ph', $parameter['ph'] ?? '-'],
            ['Salmonella', $parameter['salmonella'] ?? '-'],
            ['Angka Lempeng Total (ALT)', $parameter['alt'] ?? '-'],
        ] as [$label, $value]) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, $value);
            $this->applyTableBorder($sheet, 'A', 'B', $row);
            $row++;
        }

        $row++;

        /** @var array<string, string> $notes */
        $notes = $detail['notes'] ?? [];

        $row = $this->writeNoteSection($sheet, $row, 'A.    Kendala:', (string) ($notes['kendala'] ?? ''));
        $row = $this->writeNoteSection($sheet, $row, 'B.    Rekomendasi:', (string) ($notes['rekomendasi'] ?? ''));
        $row = $this->writeNoteSection($sheet, $row, 'C.    Kesimpulan', (string) ($notes['kesimpulan'] ?? ''));

        return $row;
    }

    private function writeNoteSection(Worksheet $sheet, int $row, string $title, string $content): int
    {
        $sheet->setCellValue('A'.$row, $title);
        $row++;

        if (trim($content) !== '') {
            foreach (preg_split("/\r\n|\n|\r/", $content) ?: [] as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $sheet->setCellValue('A'.$row, $line);
                $row++;
            }
        }

        return $row;
    }

    private function applyTableBorder(Worksheet $sheet, string $fromColumn, string $toColumn, int $row): void
    {
        $sheet->getStyle($fromColumn.$row.':'.$toColumn.$row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function trimRowsAfter(Worksheet $sheet, int $lastRow): void
    {
        $rowsToRemove = max(0, $sheet->getHighestRow() - $lastRow);

        for ($i = 0; $i < $rowsToRemove; $i++) {
            $sheet->removeRow($lastRow + 1);
        }
    }

    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $relativePath): void
    {
        $disk = Storage::disk('local');
        $directory = dirname($relativePath);

        if ($directory !== '.' && ! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'laporan-ipam-');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
