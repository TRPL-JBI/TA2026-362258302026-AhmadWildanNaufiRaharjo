<?php

namespace App\Services\Laporan;

use App\Models\User;
use App\Support\PatroliPeriode;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class PatroliTemuanWordBuilder
{
    private const FONT = 'Times New Roman';

    /**
     * @param  array<string, mixed>  $detail
     */
    public function build(User $petugas, string $periodeKey, array $detail, string $relativePath): void
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
        $this->addPendahuluan($section, $petugas, $periodeKey, $detail);
        $this->addTemuanSections($section, $detail);
        $this->addSkalaRisiko($section);

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

        $centerCell->addText('LAPORAN SAFETY PATROL K3LH', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('POLITEKNIK NEGERI BANYUWANGI', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Jl. Raya Jember kilometer 13 Labanasem, Kabat, Banyuwangi, 68461', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Telepon (0333) 636780', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $centerCell->addText('Email: poliwangi@poliwangi.ac.id ; Laman: http://www.poliwangi.ac.id', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $section->addTextBreak(1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addPendahuluan(mixed $section, User $petugas, string $periodeKey, array $detail): void
    {
        $this->addSectionHeading($section, 'PENDAHULUAN');

        $section->addText(
            'Pelaksanaan inspeksi temuan bahaya K3LH dilakukan secara berkala selama periode '
            .PatroliPeriode::rentangTanggal($periodeKey)
            .' dengan tujuan untuk:',
            null,
            ['spaceAfter' => 120],
        );

        foreach ([
            'Mengidentifikasi potensi bahaya (hazard) pada fasilitas kampus.',
            'Menilai risiko terhadap keselamatan, kesehatan, dan lingkungan.',
            'Memberikan rekomendasi perbaikan sesuai standar K3L.',
            'Meningkatkan budaya keselamatan dan kepatuhan terhadap regulasi.',
        ] as $point) {
            $section->addListItem($point, 0, null, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
        }

        $section->addTextBreak(1);
        $section->addText('Metode yang digunakan:', ['bold' => true], ['spaceAfter' => 80]);

        foreach ([
            'Inspeksi checklist berbasis lokasi',
            'Dokumentasi visual temuan bahaya',
            'Penilaian risiko berdasarkan matriks Probability × Severity',
        ] as $point) {
            $section->addListItem($point, 0, null, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
        }

        $section->addTextBreak(1);
        $section->addText(
            sprintf(
                'Petugas pelaksana: %s. Total %d lokasi diinspeksi dengan %d temuan bahaya.',
                $petugas->nama_lengkap,
                (int) ($detail['lokasi_count'] ?? 0),
                (int) ($detail['temuan_count'] ?? 0),
            ),
            null,
            ['spaceAfter' => 120],
        );

        $section->addTextBreak(1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addTemuanSections(mixed $section, array $detail): void
    {
        $this->addSectionHeading($section, 'HASIL INSPEKSI TEMUAN BAHAYA');

        /** @var list<array<string, mixed>> $inspeksi */
        $inspeksi = $detail['inspeksi'] ?? [];

        foreach ($inspeksi as $index => $row) {
            $this->addLokasiSection($section, $index + 1, $row);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function addLokasiSection(mixed $section, int $number, array $row): void
    {
        $section->addText(
            sprintf(
                '%d. %s',
                $number,
                strtoupper((string) ($row['lokasi'] ?? '-')),
            ),
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 80],
        );

        $section->addText(
            'Checklist: '.(string) ($row['checklist'] ?? '-')
            .' | Tanggal: '.(string) ($row['tanggal'] ?? '-')
            .' | Waktu: '.(string) ($row['waktu'] ?? '-'),
            ['size' => 10],
            ['spaceAfter' => 80],
        );

        /** @var list<array<string, mixed>> $details */
        $details = $row['details'] ?? [];
        $temuanItems = array_values(array_filter(
            $details,
            fn (array $item) => ($item['status'] ?? '') === 'Tidak',
        ));

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        foreach (['NO', 'TEMUAN', 'ANALISIS RESIKO', 'LEVEL RESIKO', 'REKOMENDASI'] as $heading) {
            $cell = $table->addCell(1800, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
            $cell->addText($heading, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        if ($temuanItems === []) {
            $table->addRow();
            $table->addCell(800)->addText('1', ['size' => 10], ['alignment' => Jc::CENTER]);
            $merged = $table->addCell(8200, ['gridSpan' => 4]);
            $merged->addText('Tidak ada temuan bahaya pada lokasi ini.', ['size' => 10]);
        } else {
            foreach ($temuanItems as $itemIndex => $item) {
                $table->addRow();
                $table->addCell(800)->addText((string) ($itemIndex + 1), ['size' => 10], ['alignment' => Jc::CENTER]);
                $table->addCell(2200)->addText((string) ($item['nama_item'] ?? '-'), ['size' => 10]);
                $table->addCell(2200)->addText((string) ($item['analisa_risiko'] ?? '-'), ['size' => 10]);
                $table->addCell(1400)->addText((string) ($item['level_risiko'] ?? '-'), ['size' => 10], ['alignment' => Jc::CENTER]);
                $table->addCell(2400)->addText((string) ($item['rekomendasi'] ?? '-'), ['size' => 10]);
            }
        }

        $hasFoto = collect($temuanItems)->contains(
            fn (array $item) => ! empty($item['foto_paths'] ?? []),
        );

        if ($hasFoto) {
            $section->addTextBreak(1);
            $section->addText('Dokumentasi Foto Temuan:', ['bold' => true, 'size' => 11], ['spaceAfter' => 80]);

            foreach ($temuanItems as $item) {
                $fotoPaths = $item['foto_paths'] ?? [];
                if ($fotoPaths === []) {
                    continue;
                }

                $section->addText(
                    (string) ($item['nama_item'] ?? '-'),
                    ['italic' => true, 'size' => 10],
                    ['spaceAfter' => 60],
                );
                $this->addPhotos($section, $fotoPaths);
            }
        }

        $section->addTextBreak(1);
    }

    private function addSkalaRisiko(mixed $section): void
    {
        $this->addSectionHeading($section, 'TABEL TEMUAN – ANALISIS RISIKO');

        $section->addText('Skala risiko:', ['bold' => true], ['spaceAfter' => 80]);

        foreach ([
            'Rendah (1–3)',
            'Sedang (4–6)',
            'Tinggi (8–12)',
            'Sangat Tinggi (15–25)',
        ] as $point) {
            $section->addListItem($point, 0, null, ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);
        }
    }

    private function addSectionHeading(mixed $section, string $text): void
    {
        $section->addText(strtoupper($text), ['bold' => true, 'size' => 12], ['spaceAfter' => 120, 'spaceBefore' => 120]);
    }

    /**
     * @param  list<string>  $fotoPaths
     */
    private function addPhotos(mixed $section, array $fotoPaths): void
    {
        foreach ($fotoPaths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

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

        $tempPath = tempnam(sys_get_temp_dir(), 'patroli-temuan-laporan-');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
