<?php

namespace App\Services\Laporan;

use App\Support\IpalTriwulan;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class IpalSwapantauWordBuilder
{
    private const FONT = 'Times New Roman';

    private const COVER_COMPANY = 'POLITEKNIK NEGERI BANYUWANGI';

    private const COVER_ADDRESS = 'Jalan Jl. Raya Jember KM 13 Kelurahan/Desa Labanasem Kecamatan Kabat Kabupaten Banyuwangi';

    private const FIELD_PLACEHOLDER = '................................................................';

    private const SHORT_PLACEHOLDER = '………………';

    /** @var array<string, string|null> */
    private const IDENTITAS_KNOWN = [
        'nama_perusahaan' => 'Politeknik Negeri Banyuwangi',
        'jenis_badan_hukum' => 'PTN-BH',
        'alamat' => 'Jl. Raya Jember KM 13, Labanasem, Kabat, Banyuwangi 68461',
        'telepon' => '(0333) 636780',
        'email' => 'poliwangi@poliwangi.ac.id',
        'status_pemodalan' => 'APBN',
        'bidang_usaha' => 'Pendidikan Tinggi Vokasi',
    ];

    /**
     * @param  array<string, mixed>  $detail
     */
    public function build(array $detail, string $relativePath): void
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

        $this->addCover($section, $detail);
        $this->addIdentitas($section);
        $this->addLokasi($section);
        $this->addPengelolaanHeader($section, $detail);
        $this->addDailyMonitoringSections($section, $detail);
        $this->addAnalisaSection($section);
        $this->addDampakTable($section, $detail);
        $this->addLampiranNotice($section);
        $this->addFooterInfo($section, $detail);

        $this->saveDocument($phpWord, $relativePath);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addCover(mixed $section, array $detail): void
    {
        $triwulan = (int) ($detail['triwulan'] ?? 1);

        $section->addText(
            'LAPORAN PEMANTAUAN AIR LIMBAH',
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 160],
        );

        $section->addText(
            self::COVER_COMPANY,
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 160],
        );

        $section->addTextBreak(2);

        $logoTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $logoTable->addRow(2200);
        $logoCell = $logoTable->addCell(9000, ['valign' => 'center']);
        $this->addAssetImage($logoCell, 'image1.png', 120, 120);

        $section->addTextBreak(2);

        $section->addText(
            sprintf(
                'PERIODE %s (%s)',
                IpalTriwulan::romanLabel($triwulan),
                IpalTriwulan::periodeRentang($triwulan),
            ),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 40],
        );

        $section->addTextBreak(8);

        $section->addText(
            self::COVER_COMPANY,
            ['bold' => true, 'italic' => true, 'underline' => 'single', 'size' => 11],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 80],
        );

        $lineTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $lineTable->addRow(120);
        $lineTable->addCell(9000, [
            'borderTopSize' => 18,
            'borderTopColor' => '000000',
            'borderLeftSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 0,
        ]);

        $section->addText(
            self::COVER_ADDRESS,
            ['size' => 11],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 0],
        );

        $section->addPageBreak();
    }

    private function addIdentitas(mixed $section): void
    {
        $this->addSectionHeading($section, 'A. IDENTITAS PERUSAHAAN');

        $this->addNumberedField($section, 1, 'Nama Perusahaan/Pemrakarsa', self::IDENTITAS_KNOWN['nama_perusahaan']);
        $this->addNumberedField(
            $section,
            2,
            'Jenis Badan Hukum : PT/CV/Koperasi/UD/BUMD/BUMN',
            self::IDENTITAS_KNOWN['jenis_badan_hukum'],
        );
        $this->addNumberedField(
            $section,
            3,
            'Alamat Perusahaan/Kegiatan Usaha/Pemrakarsa',
            self::IDENTITAS_KNOWN['alamat'],
        );
        $this->addNumberedField(
            $section,
            4,
            'Nomor Telepon : (kode wilayah)',
            self::IDENTITAS_KNOWN['telepon'],
        );
        $this->addNumberedField($section, 5, 'Nomor Fax. : (kode wilayah)', null);
        $this->addNumberedField($section, 6, 'E-mail', self::IDENTITAS_KNOWN['email']);
        $this->addNumberedField(
            $section,
            7,
            'Status pemodalan : PMA/PMDN/APBD/APBN',
            self::IDENTITAS_KNOWN['status_pemodalan'],
        );
        $this->addNumberedField(
            $section,
            8,
            'Bidang usaha dan atau kegiatan',
            self::IDENTITAS_KNOWN['bidang_usaha'],
        );
        $this->addNumberedField($section, 9, 'SK UKL-UPL / AMDAL / Izin Lingkungan yang disetujui', null);
        $section->addText(
            sprintf('10. Penanggung Jawab : %s (Nama dan Jabatan)', self::FIELD_PLACEHOLDER),
            ['size' => 11],
            ['spaceAfter' => 60],
        );
        $this->addNumberedField($section, 11, 'Izin lainnya (lampirkan)', null);

        $section->addTextBreak(1);
    }

    private function addLokasi(mixed $section): void
    {
        $this->addSectionHeading($section, 'B. LOKASI USAHA DAN ATAU KEGIATAN');

        $section->addText(
            'Tuliskan secara jelas lokasi usaha dan atau kegiatan (alamat lengkap dan nomor telepon). '
            .'Lengkapi dengan peta dan koordinat :',
            ['size' => 11],
            ['spaceAfter' => 80],
        );

        $section->addText(
            sprintf(
                'Kegiatan %s berada di alamat %s (jalan, RT/RW, Dusun, Desa, Kecamatan, Kabupaten) '
                .'dan berada pada titik koordinat : %s LS dan %s BT',
                $this->fieldValue(self::IDENTITAS_KNOWN['nama_perusahaan']),
                $this->fieldValue('Jl. Raya Jember KM 13, Labanasem, Kabat, Banyuwangi'),
                self::FIELD_PLACEHOLDER,
                self::FIELD_PLACEHOLDER,
            ),
            ['size' => 11],
            ['spaceAfter' => 80],
        );

        $section->addText(
            'Dengan batas – batas wilayah kegiatan sebagai berikut :',
            ['size' => 11],
            ['spaceAfter' => 60],
        );

        foreach (['Batas Utara', 'Batas Selatan', 'Batas Barat', 'Batas Timur'] as $label) {
            $section->addText(
                sprintf('%s : %s (jalan/pemukiman/industri dsb)', $label, self::FIELD_PLACEHOLDER),
                ['size' => 11],
                ['spaceAfter' => 40],
            );
        }

        $section->addTextBreak(1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addPengelolaanHeader(mixed $section, array $detail): void
    {
        $section->addText(
            sprintf(
                'Laporan Pengelolaan Limbah Cair %s Triwulan %s',
                self::COVER_COMPANY,
                (string) ($detail['triwulan_key'] ?? ''),
            ),
            ['bold' => true, 'size' => 11],
            ['spaceAfter' => 120],
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addDailyMonitoringSections(mixed $section, array $detail): void
    {
        /** @var list<array<string, mixed>> $bulanList */
        $bulanList = $detail['bulan_list'] ?? [];
        $tahun = (int) ($detail['tahun'] ?? date('Y'));
        $tableNumber = 0;

        foreach ($bulanList as $bulan) {
            /** @var array<int, array<string, string>> $entriesByDay */
            $entriesByDay = (array) ($bulan['entries_by_day'] ?? []);

            if ($entriesByDay === []) {
                continue;
            }

            $tableNumber++;
            $bulanNama = (string) ($bulan['nama'] ?? '-');

            $section->addText(
                sprintf(
                    'Tabel %d. Catatan Pemantauan Air Limbah Harian selama Bulan %s Tahun %d',
                    $tableNumber,
                    $bulanNama,
                    $tahun,
                ),
                ['bold' => true, 'size' => 11],
                ['spaceAfter' => 80],
            );

            $this->addDailyMonitoringTable($section, $entriesByDay);

            $section->addTextBreak(1);
        }
    }

    /**
     * @param  array<int, array<string, string>>  $entriesByDay
     */
    private function addDailyMonitoringTable(mixed $section, array $entriesByDay): void
    {
        $table = $this->createTable($section);
        $this->addTableHeader($table, [
            'No.',
            'Tanggal Pencatatan',
            'Debit Input Air Limbah',
            'Debit Output Air Limbah',
            'pH',
            'Suhu (Celcius)',
        ]);

        ksort($entriesByDay, SORT_NUMERIC);

        $rowNumber = 0;

        foreach ($entriesByDay as $entry) {
            $rowNumber++;

            $table->addRow();
            $table->addCell(500)->addText((string) $rowNumber, ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(1700)->addText((string) ($entry['tanggal_label'] ?? ''), ['size' => 9]);
            $debitInputCell = $table->addCell(1500, ['valign' => 'center']);
            $this->addCubicMeterToCell($debitInputCell, $entry['debit_input'] ?? null);

            $debitOutputCell = $table->addCell(1500, ['valign' => 'center']);
            $this->addCubicMeterToCell($debitOutputCell, $entry['debit_output'] ?? null);

            $table->addCell(800)->addText(
                $this->fieldValue($entry['ph'] ?? null, self::SHORT_PLACEHOLDER),
                ['size' => 9],
                ['alignment' => Jc::CENTER],
            );
            $table->addCell(900)->addText(
                $this->fieldValue($entry['suhu'] ?? null, self::SHORT_PLACEHOLDER),
                ['size' => 9],
                ['alignment' => Jc::CENTER],
            );
        }
    }

    private function addAnalisaSection(mixed $section): void
    {
        $section->addText(
            'Adapun analisa trend hasil pengujian sampel air limbah yang dilakukan oleh Laboratorium Lingkungan '
            .'(sesuai dengan lampiran Hasil Uji Laboratorium) '.self::FIELD_PLACEHOLDER.' Adalah sebagai berikut :',
            ['size' => 11],
            ['spaceAfter' => 80],
        );

        $section->addText(
            '(Grafik hasil uji setiap paremeter air limbah dibandingkan dengan Baku Mutu Air Limbah)',
            ['size' => 11, 'italic' => true],
            ['spaceAfter' => 120],
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addDampakTable(mixed $section, array $detail): void
    {
        $section->addText(
            sprintf(
                'Tabel Pengelolaan dan Pemantauan Air Limbah %s',
                self::COVER_COMPANY,
            ),
            ['bold' => true, 'size' => 11],
            ['spaceAfter' => 80],
        );

        /** @var array<string, string> $dampak */
        $dampak = (array) ($detail['dampak'] ?? []);

        $table = $this->createTable($section);
        $this->addTableHeader($table, [
            'No.',
            'Jenis Dampak',
            'Sumber Dampak',
            'Parameter Pemantauan',
            'Tolak Ukur',
            'Lokasi Pengelolaan',
            'Evaluasi / Hasil',
            'Tindakan Perbaikan',
        ]);

        $table->addRow();
        $table->addCell(500)->addText('1', ['size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1200)->addText($this->fieldValue($dampak['jenis_dampak'] ?? null, self::SHORT_PLACEHOLDER), ['size' => 9]);
        $table->addCell(1200)->addText($this->fieldValue($dampak['sumber_dampak'] ?? null, self::SHORT_PLACEHOLDER), ['size' => 9]);
        $table->addCell(1400)->addText($this->fieldValue($dampak['parameter_pemantauan'] ?? null, self::SHORT_PLACEHOLDER), ['size' => 9]);
        $table->addCell(1200)->addText($this->fieldValue($dampak['tolak_ukur'] ?? null, self::SHORT_PLACEHOLDER), ['size' => 9]);
        $table->addCell(1300)->addText(
            $this->fieldValue(
                $dampak['lokasi_pengelolaan'] ?? null,
                'Pada lokasi IPAL kegiatan '.self::COVER_COMPANY.' dengan titik koordinat IPAL : '.self::FIELD_PLACEHOLDER,
            ),
            ['size' => 9],
        );
        $table->addCell(1300)->addText($this->fieldValue($dampak['evaluasi_hasil'] ?? null, self::FIELD_PLACEHOLDER), ['size' => 9]);
        $table->addCell(1300)->addText($this->fieldValue($dampak['tindakan_perbaikan'] ?? null, self::FIELD_PLACEHOLDER), ['size' => 9]);

        $section->addTextBreak(1);
    }

    private function addLampiranNotice(mixed $section): void
    {
        $section->addText(
            'LAMPIRKAN SEMUA HASIL UJI LAB SETIAP BULANNYA!',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120],
        );
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addFooterInfo(mixed $section, array $detail): void
    {
        $section->addText(
            sprintf(
                'Laporan ini digenerate pada %s oleh %s.',
                now()->format('d/m/Y H:i'),
                (string) ($detail['petugas'] ?? '-'),
            ),
            ['size' => 10, 'italic' => true],
        );
    }

    private function addCubicMeterToCell(mixed $cell, ?string $value): void
    {
        $value = trim((string) $value);
        $prefix = $value !== '' ? $value.' m' : self::SHORT_PLACEHOLDER.' m';

        $run = $cell->addTextRun(['alignment' => Jc::CENTER]);
        $run->addText($prefix, ['size' => 9]);
        $run->addText('3', ['size' => 7, 'superScript' => true]);
    }

    /**
     * @param  array<string, mixed>  $fontStyle
     */
    private function addNumberedField(
        mixed $section,
        int $number,
        string $label,
        ?string $value,
        array $fontStyle = ['size' => 11],
    ): void {
        $section->addText(
            sprintf('%d. %s : %s', $number, $label, $this->fieldValue($value)),
            $fontStyle,
            ['spaceAfter' => 60],
        );
    }

    private function fieldValue(?string $value, string $placeholder = self::FIELD_PLACEHOLDER): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $placeholder;
    }

    private function createTable(mixed $section): mixed
    {
        return $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
    }

    /**
     * @param  list<string>  $headers
     */
    private function addTableHeader(mixed $table, array $headers): void
    {
        $table->addRow();
        foreach ($headers as $heading) {
            $cell = $table->addCell(1100, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
            $cell->addText($heading, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        }
    }

    private function addSectionHeading(mixed $section, string $text): void
    {
        $section->addText($text, ['bold' => true, 'size' => 12], ['spaceAfter' => 100, 'spaceBefore' => 100]);
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

        $tempPath = tempnam(sys_get_temp_dir(), 'laporan-ipal-');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
