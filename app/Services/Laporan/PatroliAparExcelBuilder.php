<?php

namespace App\Services\Laporan;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PatroliAparExcelBuilder
{
    private const TEMPLATE_PATH = 'templates/laporan/inventaris-apar.xlsx';

    private const HEADER_ROW = 3;

    private const DATA_START_ROW = 4;

    /**
     * @param  array<string, mixed>  $detail
     */
    public function build(User $petugas, array $detail, string $relativePath): void
    {
        $spreadsheet = IOFactory::load(resource_path(self::TEMPLATE_PATH));
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'INVENTARIS APAR DI POLITEKNIK NEGERI BANYUWANGI');
        $sheet->setCellValue(
            'A2',
            sprintf(
                'Periode: %s | Petugas: %s | Tanggal generate: %s',
                (string) ($detail['periode_label'] ?? '-'),
                $petugas->nama_lengkap,
                now()->format('d/m/Y H:i'),
            ),
        );

        $this->resetBodyRows($sheet);

        /** @var list<array<string, mixed>> $pemeriksaan */
        $pemeriksaan = $detail['pemeriksaan'] ?? [];
        $lastDataRow = $this->fillDataRows($sheet, $pemeriksaan);
        $lastRow = $this->writeSummaryAndFooter($sheet, $lastDataRow);

        $this->trimRowsAfter($sheet, $lastRow);

        $this->saveSpreadsheet($spreadsheet, $relativePath);
    }

    private function resetBodyRows(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        if ($highestRow > self::HEADER_ROW) {
            $sheet->removeRow(self::DATA_START_ROW, $highestRow - self::HEADER_ROW);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $pemeriksaan
     */
    private function fillDataRows(Worksheet $sheet, array $pemeriksaan): int
    {
        if ($pemeriksaan === []) {
            return self::DATA_START_ROW;
        }

        $row = self::DATA_START_ROW;
        $no = 1;

        $grouped = collect($pemeriksaan)
            ->sortBy([
                ['lokasi', 'asc'],
                ['kode_apar', 'asc'],
            ])
            ->groupBy('lokasi');

        foreach ($grouped as $lokasiNama => $items) {
            $sheet->setCellValue('B'.$row, (string) $lokasiNama);
            $this->applyTableBorder($sheet, $row);
            $row++;

            /** @var Collection<int, array<string, mixed>> $items */
            foreach ($items as $item) {
                $sheet->setCellValue('A'.$row, $no++);
                $sheet->setCellValue('B'.$row, $this->lokasiAparLabel($item));
                $sheet->setCellValue('C'.$row, 1);
                $sheet->setCellValue('D'.$row, (string) ($item['jenis_kapasitas'] ?? '-'));
                $sheet->setCellValue('E'.$row, $this->formatKondisi($item));
                $sheet->setCellValue('F'.$row, $this->formatKeterangan($item));
                $this->applyTableBorder($sheet, $row);
                $row++;
            }
        }

        return $row - 1;
    }

    private function writeSummaryAndFooter(Worksheet $sheet, int $lastDataRow): int
    {
        if ($lastDataRow < self::DATA_START_ROW) {
            return self::HEADER_ROW;
        }

        $sumRow = $lastDataRow + 2;
        $firstDataRowWithJumlah = $this->firstRowWithJumlah($sheet, $lastDataRow);

        $sheet->setCellValue(
            'C'.$sumRow,
            sprintf('=SUM(C%d:C%d)', $firstDataRowWithJumlah, $lastDataRow),
        );
        $this->applyTableBorder($sheet, $sumRow);

        $ttdRow = $sumRow + 3;
        $sheet->setCellValue('F'.$ttdRow, 'TTD');
        $sheet->setCellValue('F'.($ttdRow + 1), 'P2K3L POLIWANGI');

        return $ttdRow + 1;
    }

    private function firstRowWithJumlah(Worksheet $sheet, int $lastDataRow): int
    {
        for ($row = self::DATA_START_ROW; $row <= $lastDataRow; $row++) {
            if ($sheet->getCell('C'.$row)->getValue() !== null) {
                return $row;
            }
        }

        return self::DATA_START_ROW;
    }

    private function applyTableBorder(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
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

    /**
     * @param  array<string, mixed>  $item
     */
    private function lokasiAparLabel(array $item): string
    {
        $keterangan = trim((string) ($item['keterangan_apar'] ?? ''));
        if ($keterangan !== '') {
            return $keterangan;
        }

        return (string) ($item['kode_apar'] ?? '-');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatKondisi(array $item): string
    {
        $segel = strtolower((string) ($item['kondisi_segel'] ?? ''));
        $tabung = strtolower((string) ($item['kondisi_tabung'] ?? ''));

        if (str_contains($segel, 'tidak') || $segel === 'tidak-tersegel') {
            return 'terbuka';
        }

        if ($tabung === 'baik' || str_contains($tabung, 'baik')) {
            return 'Baik tersegel';
        }

        return 'tersegel';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatKeterangan(array $item): string
    {
        $parts = [];

        $expiredYear = trim((string) ($item['tanggal_expired_year'] ?? ''));
        if ($expiredYear !== '') {
            $parts[] = 'ex.'.$expiredYear;
        }

        $catatan = trim((string) ($item['catatan'] ?? ''));
        if ($catatan !== '') {
            $parts[] = $catatan;
        }

        return implode(', ', $parts);
    }

    private function saveSpreadsheet(Spreadsheet $spreadsheet, string $relativePath): void
    {
        $disk = Storage::disk('local');
        $directory = dirname($relativePath);

        if ($directory !== '.' && ! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'patroli-laporan-apar-');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
