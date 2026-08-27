<?php

namespace App\Services\Laporan;

use App\Models\User;
use App\Support\LaporanInsidenKorban;
use App\Support\PatroliPeriode;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class TindakLanjutWordBuilder
{
    private const FONT = 'Times New Roman';

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function buildPeriode(User $petugas, string $periodeKey, array $items, string $relativePath): void
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 1080,
            'marginRight' => 900,
        ]);

        $this->addKop($section);
        $this->addPendahuluan($section, $petugas, $periodeKey, $items);

        $selesai = $this->filterByStatus($items, 'Selesai');
        $berlangsung = $this->filterByStatus($items, 'Dalam Proses');
        $belum = $this->filterByStatus($items, 'Menunggu Tindakan');

        $this->addItemSection($section, 'A. SUDAH DITINDAKLANJUTI (SELESAI)', $selesai);
        $this->addItemSection($section, 'B. SEDANG BERLANGSUNG (DALAM PROSES)', $berlangsung);
        $this->addItemSection($section, 'C. BELUM DITINDAKLANJUTI (MENUNGGU TINDAKAN)', $belum);

        $section->addTextBreak(1);
        $section->addText(
            'Dokumen ini digenerate otomatis saat periode tindak lanjut ditandai selesai.',
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER],
        );

        $this->saveDocument($phpWord, $relativePath);
    }

    private function addKop(mixed $section): void
    {
        $table = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow(2200);
        $leftCell = $table->addCell(2500, ['valign' => 'center']);
        $centerCell = $table->addCell(5000, ['valign' => 'center']);
        $rightCell = $table->addCell(2500, ['valign' => 'center']);

        $this->addAssetImage($leftCell, 'image1.png', 95, 95);
        $this->addAssetImage($rightCell, 'image2.jpeg', 85, 85);

        $centerCell->addText('LAPORAN REKAPITULASI TINDAK LANJUT K3LH', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('POLITEKNIK NEGERI BANYUWANGI', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Jl. Raya Jember kilometer 13 Labanasem, Kabat, Banyuwangi, 68461', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Telepon (0333) 636780', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Email: poliwangi@poliwangi.ac.id ; Laman: http://www.poliwangi.ac.id', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $section->addTextBreak(1);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addPendahuluan(mixed $section, User $petugas, string $periodeKey, array $items): void
    {
        $this->addSectionHeading($section, 'PENDAHULUAN');

        $selesai = count($this->filterByStatus($items, 'Selesai'));
        $berlangsung = count($this->filterByStatus($items, 'Dalam Proses'));
        $belum = count($this->filterByStatus($items, 'Menunggu Tindakan'));

        $section->addText(
            'Rekapitulasi tindak lanjut K3LH disusun untuk periode '
            .PatroliPeriode::rentangTanggal($periodeKey)
            .' dengan tujuan untuk:',
            null,
            ['spaceAfter' => 120],
        );

        foreach ([
            'Mencatat status perbaikan temuan bahaya dan laporan insiden.',
            'Memantau item yang sudah selesai, sedang berlangsung, maupun belum ditindaklanjuti.',
            'Menyediakan arsip resmi saat periode tindak lanjut ditutup.',
        ] as $point) {
            $section->addListItem($point, 0, null, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
        }

        $section->addTextBreak(1);
        $section->addText(
            sprintf(
                'Petugas penutup periode: %s. Periode: %s. Total %d item (Selesai: %d, Dalam Proses: %d, Menunggu Tindakan: %d).',
                $petugas->nama_lengkap,
                PatroliPeriode::displayLabel($periodeKey),
                count($items),
                $selesai,
                $berlangsung,
                $belum,
            ),
            null,
            ['spaceAfter' => 80],
        );

        $section->addTextBreak(1);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function addItemSection(mixed $section, string $heading, array $items): void
    {
        $this->addSectionHeading($section, $heading);

        if ($items === []) {
            $section->addText('Tidak ada item pada kategori ini.', ['italic' => true, 'size' => 10], ['spaceAfter' => 200]);

            return;
        }

        foreach (array_values($items) as $index => $item) {
            $section->addText(
                'Item '.($index + 1),
                ['bold' => true, 'size' => 11],
                ['spaceAfter' => 80],
            );

            $this->addItemTable($section, $item);
            $this->addItemPhotos($section, $item);
            $section->addTextBreak(1);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function addItemTable(mixed $section, array $item): void
    {
        $rows = $this->isInsiden($item)
            ? $this->insidenRows($item)
            : $this->temuanRows($item);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        $headerLabel = $table->addCell(3200, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
        $headerLabel->addText('URAIAN', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        $headerValue = $table->addCell(6800, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
        $headerValue->addText('KETERANGAN', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $labelCell = $table->addCell(3200, ['valign' => 'top']);
            $labelCell->addText($label, ['bold' => true, 'size' => 10]);
            $valueCell = $table->addCell(6800, ['valign' => 'top']);
            $valueCell->addText($value, ['size' => 10]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<array{0: string, 1: string}>
     */
    private function temuanRows(array $item): array
    {
        return [
            ['Jenis', (string) ($item['jenis'] ?? 'Temuan Patroli')],
            ['Periode asal', (string) ($item['periode_asal_label'] ?? '-')],
            ['Tanggal laporan', (string) ($item['tanggal'] ?? '-')],
            ['Lokasi', (string) ($item['lokasi'] ?? '-')],
            ['Item temuan / bahaya', (string) ($item['deskripsi'] ?? '-')],
            ['Tingkat risiko', $this->formatRisiko($item)],
            ['Status perbaikan', (string) ($item['status'] ?? '-')],
            ['Tanggal mulai perbaikan', $this->nullableText($item['tanggal_mulai'] ?? null)],
            ['Tanggal selesai', $this->nullableText($item['tanggal_selesai'] ?? null)],
            ['Analisa risiko', $this->nullableText($item['analisa_risiko'] ?? null)],
            ['Rekomendasi', $this->nullableText($item['rekomendasi'] ?? null)],
            ['Keterangan tindak lanjut', $this->nullableText($item['catatan'] ?? null)],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<array{0: string, 1: string}>
     */
    private function insidenRows(array $item): array
    {
        return [
            ['Jenis', (string) ($item['jenis'] ?? 'Laporan Insiden Darurat (Satpam)')],
            ['Periode asal', (string) ($item['periode_asal_label'] ?? '-')],
            ['Tanggal kejadian', (string) ($item['tanggal'] ?? '-')],
            ['Pelapor (Satpam)', $this->nullableText($item['pelapor'] ?? null)],
            ['Jenis insiden', $this->nullableText($item['jenis_insiden'] ?? $item['deskripsi'] ?? null)],
            ['Lokasi kejadian', (string) ($item['lokasi'] ?? '-')],
            ['Korban', $this->formatKorban($item)],
            ['Prioritas', (string) ($item['risiko'] ?? 'Darurat')],
            ['Status perbaikan', (string) ($item['status'] ?? '-')],
            ['Tanggal mulai perbaikan', $this->nullableText($item['tanggal_mulai'] ?? null)],
            ['Tanggal selesai', $this->nullableText($item['tanggal_selesai'] ?? null)],
            ['Kronologi kejadian', $this->nullableText($item['kronologi'] ?? null)],
            ['Keterangan tindak lanjut', $this->nullableText($item['catatan'] ?? null)],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function addItemPhotos(mixed $section, array $item): void
    {
        $dokumentasiPaths = $this->resolvePhotoPaths($item['foto_dokumentasi'] ?? []);
        $buktiPaths = $this->resolvePhotoPaths($item['foto_bukti'] ?? []);

        if ($dokumentasiPaths === [] && $buktiPaths === []) {
            return;
        }

        if ($dokumentasiPaths !== []) {
            $section->addText(
                $this->isInsiden($item) ? 'Foto TKP / Kondisi:' : 'Foto Dokumentasi Patroli:',
                ['bold' => true, 'size' => 10],
                ['spaceBefore' => 80, 'spaceAfter' => 60],
            );
            $this->addPhotos($section, $dokumentasiPaths);
        }

        if ($buktiPaths !== []) {
            $section->addText(
                'Foto Bukti Perbaikan:',
                ['bold' => true, 'size' => 10],
                ['spaceBefore' => 80, 'spaceAfter' => 60],
            );
            $this->addPhotos($section, $buktiPaths);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isInsiden(array $item): bool
    {
        return ($item['ref_type'] ?? null) === 'insiden';
    }

    /**
     * @param  mixed  $entries
     * @return list<string>
     */
    private function resolvePhotoPaths(mixed $entries): array
    {
        if (! is_array($entries)) {
            return [];
        }

        $paths = [];

        foreach ($entries as $entry) {
            if (is_string($entry) && $entry !== '') {
                $paths[] = $entry;

                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $path = $entry['storedPath'] ?? $entry['stored_path'] ?? null;

            if (is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  list<string>  $fotoPaths
     */
    private function addPhotos(mixed $section, array $fotoPaths): void
    {
        foreach ($fotoPaths as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            $section->addImage(Storage::disk('public')->path($path), [
                'width' => 280,
                'height' => 210,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatRisiko(array $item): string
    {
        $risiko = (string) ($item['risiko'] ?? '-');
        $skor = $item['skor'] ?? null;

        if ($skor === null || $skor === '') {
            return $risiko;
        }

        return $risiko.' ('.$skor.')';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function formatKorban(array $item): string
    {
        $list = LaporanInsidenKorban::decode(
            isset($item['korban']) ? (string) $item['korban'] : null,
            isset($item['usia_korban']) ? (string) $item['usia_korban'] : null,
            isset($item['unit_prodi']) ? (string) $item['unit_prodi'] : null,
            isset($item['jabatan_korban']) ? (string) $item['jabatan_korban'] : null,
            isset($item['status_korban']) ? (string) $item['status_korban'] : null,
        );

        return LaporanInsidenKorban::summary($list);
    }

    private function nullableText(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        $text = trim((string) $value);

        return $text === '' ? '-' : $text;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function filterByStatus(array $items, string $status): array
    {
        return array_values(array_filter($items, fn (array $item) => ($item['status'] ?? '') === $status));
    }

    private function addSectionHeading(mixed $section, string $text): void
    {
        $section->addText(strtoupper($text), ['bold' => true, 'size' => 12], ['spaceAfter' => 120, 'spaceBefore' => 120]);
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
        $disk = Storage::disk('local');
        $directory = dirname($relativePath);

        if ($directory !== '.' && ! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'tindak-lanjut-laporan-');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
