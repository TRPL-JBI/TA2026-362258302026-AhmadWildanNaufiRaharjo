<?php

namespace App\Services\Laporan;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class LaporanInsidenWordBuilder
{
    private const FONT = 'Times New Roman';

    // Lebar kolom (twip). Total ~12240 untuk landscape A4 margin 720 kiri/kanan.
    // No | Nama | Usia | th | Unit/Prodi | Jabatan | Tanggal | Jam | Lokasi | Status
    private const COL_WIDTHS = [600, 2200, 700, 400, 1600, 1600, 1200, 800, 1940, 1200];

    /**
     * @param  array<string, mixed>  $detail
     */
    public function build(array $detail, string $relativePath): void
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'paperSize' => 'A4',
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        $this->addKop($section, $detail);
        $this->addDataTable($section, $detail);
        $this->addKronologi($section, $detail);
        $this->addPhotos($section, $detail['foto_paths'] ?? []);
        $this->addSignatures($section, $detail);

        $this->saveDocument($phpWord, $relativePath);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addKop(mixed $section, array $detail): void
    {
        $table = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow(1800);
        $leftCell   = $table->addCell(1800, ['valign' => 'center']);
        $centerCell = $table->addCell(8400, ['valign' => 'center']);
        $rightCell  = $table->addCell(1800, ['valign' => 'center']);

        $this->addAssetImage($leftCell, 'image1.png', 80, 80);
        $this->addAssetImage($rightCell, 'image2.jpeg', 72, 72);

        $centerCell->addText('KEMENTERIAN RISET, TEKNOLOGI DAN PENDIDIKAN TINGGI', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('POLITEKNIK NEGERI BANYUWANGI', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Jl. Raya Jember Kilometer 13 Labanasem, Kabat, Banyuwangi, 68461   Telp./Faks.: (0333) 636780', ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('E-mail: poliwangi@poliwangi.ac.id ; Website: http//www.poliwangi.ac.id', ['size' => 8], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $section->addTextBreak(1);

        $jenis = (string) ($detail['jenis_insiden'] ?? 'Laporan Insiden');
        $title = $jenis === 'Kecelakaan Kerja'
            ? 'DATA KECELAKAAN KERJA'
            : 'DATA LAPORAN INSIDEN — '.strtoupper($jenis);

        $section->addText($title, ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 160]);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addDataTable(mixed $section, array $detail): void
    {
        $widths    = self::COL_WIDTHS;
        $cellStyle = ['borderSize' => 6, 'borderColor' => '000000', 'valign' => 'center'];
        $hdr       = array_merge($cellStyle, ['bgColor' => 'D9D9D9']);
        $fnt       = ['size' => 8, 'bold' => true];
        $fntNormal = ['size' => 8];
        $center    = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
        $top       = ['spaceAfter' => 0];

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        // Header baris 1: No | Korban (span 5) | Tanggal | Jam | Lokasi | Status
        $table->addRow();
        $table->addCell($widths[0], array_merge($hdr, ['vMerge' => 'restart']))->addText('No', $fnt, $center);
        $korbanW = $widths[1] + $widths[2] + $widths[3] + $widths[4] + $widths[5];
        $table->addCell($korbanW, array_merge($hdr, ['gridSpan' => 5]))->addText('Korban', $fnt, $center);
        $table->addCell($widths[6], array_merge($hdr, ['vMerge' => 'restart']))->addText('Tanggal', $fnt, $center);
        $table->addCell($widths[7], array_merge($hdr, ['vMerge' => 'restart']))->addText('Jam', $fnt, $center);
        $table->addCell($widths[8], array_merge($hdr, ['vMerge' => 'restart']))->addText('Lokasi', $fnt, $center);
        $table->addCell($widths[9], array_merge($hdr, ['vMerge' => 'restart']))->addText('Status', $fnt, $center);

        // Header baris 2: (No lanjut) | Nama | Usia | th | Unit/Prodi | Jabatan | (lanjut)
        $table->addRow();
        $table->addCell($widths[0], array_merge($hdr, ['vMerge' => 'continue']));
        $table->addCell($widths[1], $hdr)->addText('Nama', $fnt, $center);
        $table->addCell($widths[2], $hdr)->addText('Usia', $fnt, $center);
        $table->addCell($widths[3], $hdr)->addText('', $fnt, $center);
        $table->addCell($widths[4], $hdr)->addText('Unit/Prodi', $fnt, $center);
        $table->addCell($widths[5], $hdr)->addText('Jabatan', $fnt, $center);
        $table->addCell($widths[6], array_merge($hdr, ['vMerge' => 'continue']));
        $table->addCell($widths[7], array_merge($hdr, ['vMerge' => 'continue']));
        $table->addCell($widths[8], array_merge($hdr, ['vMerge' => 'continue']));
        $table->addCell($widths[9], array_merge($hdr, ['vMerge' => 'continue']));

        $korbanList = $detail['korban_list'] ?? [];
        if (! is_array($korbanList) || $korbanList === []) {
            $korbanList = [[
                'nama' => (string) ($detail['nama_korban'] ?? '-'),
                'usia' => $detail['usia_korban'] ?? null,
                'unit_prodi' => $detail['unit_prodi'] ?? null,
                'jabatan' => $detail['jabatan_korban'] ?? null,
                'status' => $detail['status_korban'] ?? null,
            ]];
        }

        $rowCount = count($korbanList);

        foreach ($korbanList as $index => $korban) {
            $usia = filled($korban['usia'] ?? null) ? (string) $korban['usia'] : '';
            $th = $usia !== '' ? 'th' : '';
            $isFirst = $index === 0;
            $vMerge = $rowCount > 1
                ? ($isFirst ? ['vMerge' => 'restart'] : ['vMerge' => 'continue'])
                : [];

            $table->addRow();
            $table->addCell($widths[0], $cellStyle)->addText((string) ($index + 1), $fntNormal, $center);
            $table->addCell($widths[1], $cellStyle)->addText((string) ($korban['nama'] ?? '-'), $fntNormal, $top);
            $table->addCell($widths[2], $cellStyle)->addText($usia, $fntNormal, $center);
            $table->addCell($widths[3], $cellStyle)->addText($th, $fntNormal, $center);
            $table->addCell($widths[4], $cellStyle)->addText((string) (($korban['unit_prodi'] ?? null) ?: '-'), $fntNormal, $top);
            $table->addCell($widths[5], $cellStyle)->addText((string) (($korban['jabatan'] ?? null) ?: '-'), $fntNormal, $top);

            if ($isFirst || $rowCount === 1) {
                $table->addCell($widths[6], array_merge($cellStyle, $vMerge))
                    ->addText((string) ($detail['tanggal'] ?? '-'), $fntNormal, $center);
                $table->addCell($widths[7], array_merge($cellStyle, $vMerge))
                    ->addText((string) ($detail['jam'] ?? '-'), $fntNormal, $center);
                $table->addCell($widths[8], array_merge($cellStyle, $vMerge))
                    ->addText((string) ($detail['lokasi'] ?? '-'), $fntNormal, $top);
            } else {
                $table->addCell($widths[6], array_merge($cellStyle, $vMerge));
                $table->addCell($widths[7], array_merge($cellStyle, $vMerge));
                $table->addCell($widths[8], array_merge($cellStyle, $vMerge));
            }

            $table->addCell($widths[9], $cellStyle)
                ->addText((string) (($korban['status'] ?? null) ?: '-'), $fntNormal, $top);
        }

        $section->addTextBreak(1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addKronologi(mixed $section, array $detail): void
    {
        $kronologi = trim((string) ($detail['kronologi'] ?? ''));
        if ($kronologi === '') {
            $kronologi = '-';
        }

        $section->addText('Kronologi', ['bold' => true, 'size' => 10], ['spaceAfter' => 40]);
        $section->addText($kronologi, ['size' => 9], ['spaceAfter' => 80]);
    }

    /**
     * @param  mixed  $photoPaths
     */
    private function addPhotos(mixed $section, mixed $photoPaths): void
    {
        if (! is_array($photoPaths) || $photoPaths === []) {
            return;
        }

        $section->addTextBreak(1);
        $section->addText('Foto Laporan', ['bold' => true, 'size' => 10], ['spaceAfter' => 120]);

        foreach (array_values($photoPaths) as $path) {
            if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $section->addImage(Storage::disk('public')->path($path), [
                'width' => 300,
                'height' => 225,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addSignatures(mixed $section, array $detail): void
    {
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $fntBold = ['bold' => true, 'size' => 10];
        $fntSm   = ['size' => 9];
        $center  = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $table->addRow();
        $table->addCell(4167, ['valign' => 'top'])->addText('Dibuat', $fntBold, $center);
        $table->addCell(4167, ['valign' => 'top'])->addText('Diperiksa', $fntBold, $center);
        $table->addCell(4166, ['valign' => 'top'])->addText('Disetujui', $fntBold, $center);

        $table->addRow(1200);
        $table->addCell(4167);
        $table->addCell(4167);
        $table->addCell(4166);

        $table->addRow();
        $table->addCell(4167, ['valign' => 'top'])->addText((string) ($detail['dibuat_oleh'] ?? '-'), $fntBold, $center);
        $table->addCell(4167, ['valign' => 'top'])->addText(' ', $fntBold, $center);
        $table->addCell(4166, ['valign' => 'top'])->addText(' ', $fntBold, $center);

        $table->addRow();
        $table->addCell(4167, ['valign' => 'top'])->addText('NIP./NIK.', $fntSm, $center);
        $table->addCell(4167, ['valign' => 'top'])->addText('NIP./NIK.', $fntSm, $center);
        $table->addCell(4166, ['valign' => 'top'])->addText('NIP./NIK.', $fntSm, $center);
    }

    private function addAssetImage(mixed $cell, string $filename, int $width, int $height): void
    {
        $path = resource_path('templates/laporan/assets/'.$filename);

        if (! is_file($path)) {
            return;
        }

        $cell->addImage($path, [
            'width' => $width,
            'height' => $height,
            'alignment' => Jc::CENTER,
        ]);
    }

    private function saveDocument(PhpWord $phpWord, string $relativePath): void
    {
        $disk      = Storage::disk('local');
        $directory = dirname($relativePath);

        if ($directory !== '.' && ! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'laporan-insiden-');
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
