<?php

namespace App\Services\Laporan;

use App\Support\B3Semester;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class B3SwapantauWordBuilder
{
    private const FONT = 'Times New Roman';

    private const COVER_COMPANY = 'POLITEKNIK NEGERI BANYUWANGI';

    private const COVER_ADDRESS = 'Jalan Jl. Raya Jember KM 13 Kelurahan/Desa Labanasem Kecamatan Kabat Kabupaten Banyuwangi';

    private const MANIFEST_LOGO = 'logo-klhk.png';

    private const FIELD_PLACEHOLDER = '................................................................';

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
        $this->addJenisLimbahTable($section, $detail);
        $this->addLogbookSections($section, $detail);
        $this->addManifestSections($section, $detail);
        $this->addFooterInfo($section, $detail);

        $this->saveDocument($phpWord, $relativePath);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addCover(mixed $section, array $detail): void
    {
        $semester = (int) ($detail['semester'] ?? 1);

        foreach ([
            'LAPORAN PEMANTAUAN PENGELOLAAN',
            'LIMBAH BAHAN BERBAHAYA DAN BERACUN',
            '(B3)',
        ] as $line) {
            $section->addText(
                $line,
                ['bold' => true, 'size' => 14],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0],
            );
        }

        $section->addTextBreak(1);

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
                strtoupper(B3Semester::label($semester)),
                B3Semester::periodeRentang($semester),
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
        $this->addPenanggungJawabField($section);
        $this->addNumberedField(
            $section,
            11,
            'Nomor Dokumen Rincian Teknis Limbah B3',
            null,
            ['size' => 11, 'color' => 'C00000'],
        );
        $this->addNumberedField(
            $section,
            12,
            'Nomor Dokumen MoU / PKS pihak pengangkut Limbah B3',
            null,
            ['size' => 11, 'color' => 'C00000'],
        );

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

        $company = self::IDENTITAS_KNOWN['nama_perusahaan'] ?? null;
        $address = 'Jl. Raya Jember KM 13, Labanasem, Kabat, Banyuwangi';

        $section->addText(
            sprintf(
                'Kegiatan %s berada di alamat %s (jalan, RT/RW, Dusun, Desa, Kecamatan, Kabupaten) '
                .'dan berada pada titik koordinat : %s LS dan %s BT',
                $this->fieldValue($company),
                $this->fieldValue($address),
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

        foreach ([
            'Batas Utara',
            'Batas Selatan',
            'Batas Barat',
            'Batas Timur',
        ] as $label) {
            $section->addText(
                sprintf('%s : %s (jalan/pemukiman/industri dsb)', $label, self::FIELD_PLACEHOLDER),
                ['size' => 11],
                ['spaceAfter' => 40],
            );
        }

        $section->addTextBreak(1);
    }

    private function addPenanggungJawabField(mixed $section): void
    {
        $section->addText(
            sprintf(
                '10. Penanggung Jawab : %s (Nama dan Jabatan)',
                self::FIELD_PLACEHOLDER,
            ),
            ['size' => 11],
            ['spaceAfter' => 60],
        );
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

    private function fieldValue(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : self::FIELD_PLACEHOLDER;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addJenisLimbahTable(mixed $section, array $detail): void
    {
        $section->addText(
            'Jenis, Sumber, Karakteristik, Jumlah, Pengemasan dan Masa Simpan Limbah B3',
            ['bold' => true, 'size' => 11],
            ['spaceAfter' => 100],
        );

        /** @var list<array<string, mixed>> $jenisList */
        $jenisList = $detail['jenis_list'] ?? [];

        $table = $this->createTable($section);
        $this->addTableHeader($table, [
            'No',
            'Nama Limbah',
            'Kode Limbah',
            'Sumber',
            'Karakteristik',
            'Pengemasan',
            'Masa Simpan (hari)',
        ]);

        if ($jenisList === []) {
            $this->addEmptyTableRow($table, 7, 'Belum ada data jenis limbah B3.');
        } else {
            foreach ($jenisList as $index => $row) {
                $table->addRow();
                $table->addCell(600)->addText((string) ($index + 1), ['size' => 10], ['alignment' => Jc::CENTER]);
                $table->addCell(1600)->addText((string) ($row['nama_limbah'] ?? '-'), ['size' => 10]);
                $table->addCell(1200)->addText((string) ($row['kode_limbah'] ?? '-'), ['size' => 10]);
                $table->addCell(1500)->addText((string) ($row['sumber_limbah'] ?? '-'), ['size' => 10]);
                $table->addCell(1300)->addText((string) ($row['karakteristik'] ?? '-'), ['size' => 10]);
                $table->addCell(1200)->addText((string) ($row['pengemasan'] ?? '-'), ['size' => 10]);
                $table->addCell(1100)->addText((string) ($row['masa_simpan_hari'] ?? '-'), ['size' => 10], ['alignment' => Jc::CENTER]);
            }
        }

        $section->addTextBreak(1);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addLogbookSections(mixed $section, array $detail): void
    {
        /** @var list<array<string, mixed>> $bulanList */
        $bulanList = $detail['logbook_bulan_list'] ?? [];
        $tahun = (string) ($detail['tahun'] ?? '');
        $tableNumber = 0;

        foreach ($bulanList as $bulan) {
            /** @var list<array<string, mixed>> $entries */
            $entries = $bulan['entries'] ?? [];

            if ($entries === []) {
                continue;
            }

            $tableNumber++;
            $bulanNama = (string) ($bulan['nama'] ?? '-');

            $section->addText(
                sprintf(
                    'Tabel %d. Catatan / Logbook Limbah B3 periode bulan %s tahun %s',
                    $tableNumber,
                    $bulanNama,
                    $tahun,
                ),
                ['bold' => true, 'size' => 11, 'color' => 'C00000'],
                ['spaceAfter' => 100],
            );

            $this->addLogbookBanner($section);
            $section->addText(
                sprintf('PERIODE: %s %s', strtoupper($bulanNama), $tahun),
                ['bold' => true, 'size' => 11],
                ['spaceAfter' => 80],
            );

            $this->addLogbookDataTable($section, $entries);
            $section->addTextBreak(1);
        }
    }

    private function addLogbookBanner(mixed $section): void
    {
        $table = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow(1200);
        $logoCell = $table->addCell(1500, ['valign' => 'center']);
        $textCell = $table->addCell(7500, ['valign' => 'center']);

        $this->addAssetImage($logoCell, 'image1.png', 70, 70);

        $textCell->addText(
            'LOGBOOK LIMBAH B3 (BAHAN BERBAHAYA DAN BERACUN)',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 40],
        );
        $textCell->addText(
            'POLITEKNIK NEGERI BANYUWANGI',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER],
        );

        $section->addTextBreak(0);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    private function addLogbookDataTable(mixed $section, array $entries): void
    {
        $table = $this->createTable($section);

        $table->addRow();
        $masukHeader = $table->addCell(6200, [
            'gridSpan' => 4,
            'bgColor' => 'D9D9D9',
            'valign' => 'center',
        ]);
        $masukHeader->addText(
            'MASUKNYA LIMBAH B3 KE TPS',
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER],
        );

        $keluarHeader = $table->addCell(2800, [
            'gridSpan' => 2,
            'bgColor' => '92D050',
            'valign' => 'center',
        ]);
        $keluarHeader->addText(
            'KELUARNYA LIMBAH B3 DARI TPS',
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER],
        );

        $table->addRow();
        foreach ([
            'Tanggal Masuk Limbah B3',
            'Jenis Limbah B3',
            'Sumber Limbah B3',
            'Jumlah Limbah B3 Masuk (Kg)',
            'Tanggal Keluar Limbah B3',
            'Jumlah Limbah B3 Keluar (Kg)',
        ] as $index => $heading) {
            $bgColor = $index < 4 ? 'EDEDED' : 'C6E0B4';
            $cell = $table->addCell(1500, ['bgColor' => $bgColor, 'valign' => 'center']);
            $cell->addText($heading, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        }

        $groups = $this->groupLogbookEntries($entries);

        foreach ($groups as $group) {
            foreach ($group as $rowIndex => $entry) {
                $table->addRow();
                $isFirst = $rowIndex === 0;
                $mergeStyle = ['valign' => 'center'];

                if ($isFirst) {
                    $table->addCell(1200, array_merge($mergeStyle, ['vMerge' => 'restart']))
                        ->addText((string) ($entry['tanggal_masuk'] ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
                    $table->addCell(1500, array_merge($mergeStyle, ['vMerge' => 'restart']))
                        ->addText((string) ($entry['jenis_limbah'] ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);
                } else {
                    $table->addCell(1200, array_merge($mergeStyle, ['vMerge' => 'continue']));
                    $table->addCell(1500, array_merge($mergeStyle, ['vMerge' => 'continue']));
                }

                $table->addCell(1800, $mergeStyle)
                    ->addText((string) ($entry['sumber_limbah'] ?? ''), ['size' => 10]);
                $table->addCell(1700, $mergeStyle)
                    ->addText((string) ($entry['jumlah_masuk_kg'] ?? ''), ['size' => 10], ['alignment' => Jc::CENTER]);

                $keluarTanggal = (string) ($entry['tanggal_keluar'] ?? '');
                $keluarJumlah = (string) ($entry['jumlah_keluar_kg'] ?? '');

                if ($this->canMergeKeluarColumn($group, 'tanggal_keluar') && $isFirst) {
                    $table->addCell(1400, array_merge($mergeStyle, ['vMerge' => 'restart']))
                        ->addText($keluarTanggal, ['size' => 10], ['alignment' => Jc::CENTER]);
                } elseif ($this->canMergeKeluarColumn($group, 'tanggal_keluar')) {
                    $table->addCell(1400, array_merge($mergeStyle, ['vMerge' => 'continue']));
                } else {
                    $table->addCell(1400, $mergeStyle)
                        ->addText($keluarTanggal, ['size' => 10], ['alignment' => Jc::CENTER]);
                }

                if ($this->canMergeKeluarColumn($group, 'jumlah_keluar_kg') && $isFirst) {
                    $table->addCell(1400, array_merge($mergeStyle, ['vMerge' => 'restart']))
                        ->addText($keluarJumlah, ['size' => 10], ['alignment' => Jc::CENTER]);
                } elseif ($this->canMergeKeluarColumn($group, 'jumlah_keluar_kg')) {
                    $table->addCell(1400, array_merge($mergeStyle, ['vMerge' => 'continue']));
                } else {
                    $table->addCell(1400, $mergeStyle)
                        ->addText($keluarJumlah, ['size' => 10], ['alignment' => Jc::CENTER]);
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<list<array<string, mixed>>>
     */
    private function groupLogbookEntries(array $entries): array
    {
        usort($entries, function (array $a, array $b) {
            return [
                (string) ($a['tanggal_masuk'] ?? ''),
                (string) ($a['jenis_limbah'] ?? ''),
                (string) ($a['sumber_limbah'] ?? ''),
            ] <=> [
                (string) ($b['tanggal_masuk'] ?? ''),
                (string) ($b['jenis_limbah'] ?? ''),
                (string) ($b['sumber_limbah'] ?? ''),
            ];
        });

        $groups = [];

        foreach ($entries as $entry) {
            $key = (string) ($entry['tanggal_masuk'] ?? '').'|'.(string) ($entry['jenis_limbah'] ?? '');
            $groups[$key][] = $entry;
        }

        return array_values($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $group
     */
    private function canMergeKeluarColumn(array $group, string $field): bool
    {
        if (count($group) <= 1) {
            return false;
        }

        $values = array_map(
            fn (array $row) => (string) ($row[$field] ?? ''),
            $group,
        );

        $nonEmpty = array_values(array_filter($values, fn (string $value) => $value !== ''));

        return $nonEmpty !== [] && count(array_unique($nonEmpty)) === 1;
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

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addManifestSections(mixed $section, array $detail): void
    {
        /** @var list<array<string, mixed>> $manifestList */
        $manifestList = $detail['manifest_list'] ?? [];

        $section->addText(
            'LAMPIRKAN SEMUA MANIFEST PENYERAHAN LIMBAH B3!',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120],
        );

        if ($manifestList === []) {
            $section->addText('Belum ada manifest penyerahan limbah B3 pada periode ini.', ['size' => 11]);

            return;
        }

        foreach ($manifestList as $manifest) {
            $this->addManifestBanner($section);
            $this->addManifestFormTable($section, $manifest);
            $section->addTextBreak(1);
        }
    }

    private function addManifestBanner(mixed $section): void
    {
        $table = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 40,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow(1400);
        $cell = $table->addCell(9000, ['valign' => 'center']);

        $this->addAssetImage($cell, self::MANIFEST_LOGO, 80, 80);

        $cell->addText(
            'MANIFES LIMBAH BAHAN BERBAHAYA DAN BERACUN',
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80],
        );
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function addManifestFormTable(mixed $section, array $manifest): void
    {
        $table = $this->createTable($section);

        $table->addRow();
        foreach (['NO', 'Judul', 'Isian'] as $heading) {
            $cell = $table->addCell($heading === 'NO' ? 900 : 3600, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
            $cell->addText($heading, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }

        $this->addManifestDataRow($table, '#', 'Nomor Manifes', (string) ($manifest['nomor_manifest'] ?? ''), true);

        $this->addManifestSectionHeader($table, 'I. Informasi Tentang Pengirim Limbah B3');
        $this->addManifestDataRow($table, '1', 'Nama dan Alamat Pengirim Limbah B3', (string) ($manifest['pengirim_nama_alamat'] ?? ''));
        $this->addManifestDataRow($table, '2', 'Nama Fasilitas Penyimpanan Limbah B3', (string) ($manifest['nama_fasilitas_penyimpanan'] ?? ''));
        $this->addManifestDataRow($table, '3', 'Data Limbah B3', '');
        $this->addManifestSubRow($table, 'A. Kode Limbah B3', (string) ($manifest['kode_limbah'] ?? ''));
        $this->addManifestSubRow($table, 'B. Nama Limbah B3', (string) ($manifest['nama_limbah'] ?? ''));
        $this->addManifestSubRow($table, 'C. Nama Teknik', (string) ($manifest['nama_teknik'] ?? ''));
        $this->addManifestSubRow($table, 'D. Periode Limbah B3 dihasilkan', (string) ($manifest['periode_limbah'] ?? ''));
        $this->addManifestSubRow($table, 'E. Karakteristik Limbah B3', (string) ($manifest['karakteristik_limbah'] ?? ''));
        $this->addManifestSubRow($table, 'F. Jenis Kemasan', (string) ($manifest['jenis_kemasan'] ?? ''));
        $this->addManifestSubRow($table, 'G. Jumlah Kemasan', (string) ($manifest['jumlah_kemasan'] ?? ''));
        $this->addManifestSubRow($table, 'H. Jumlah Limbah B3 (TON)', (string) ($manifest['jumlah_limbah_ton'] ?? ''));
        $this->addManifestDataRow($table, '4', 'Keterangan tambahan untuk Limbah B3 yang diangkut', (string) ($manifest['keterangan_tambahan'] ?? ''));
        $this->addManifestDataRow($table, '5', 'Tujuan Pengangkutan', (string) ($manifest['tujuan_pengangkutan'] ?? ''));
        $this->addManifestStatementRow(
            $table,
            'Pernyataan perusahaan Pengirim Limbah B3: Dengan ini saya menyatakan bahwa Limbah B3 yang dikirimkan '
            .'sesuai dengan data yang disampaikan di atas, telah dikemas, dilekati label dan simbol dalam keadaan baik '
            .'sesuai dengan Peraturan Pemerintah Republik Indonesia.',
        );
        $this->addManifestDataRow($table, '6', 'Nama Penanggung Jawab', (string) ($manifest['penanggung_jawab_pengirim'] ?? ''));
        $this->addManifestDataRow($table, '7', 'Jabatan', (string) ($manifest['jabatan_pj_pengirim'] ?? ''));

        $this->addManifestSectionHeader($table, 'II. Informasi Tentang Pengangkut Limbah B3');
        $this->addManifestDataRow($table, '8', 'Nama dan Alamat Pengangkut Limbah B3', (string) ($manifest['pengangkut_nama_alamat'] ?? ''));
        $this->addManifestDataRow($table, '9', 'Nomor Telepon Darurat', (string) ($manifest['no_telepon_darurat'] ?? ''));
        $this->addManifestDataRow($table, '10', 'Jumlah Rit', (string) ($manifest['jumlah_ril'] ?? ''));
        $this->addManifestDataRow($table, '11', 'Identitas Alat Angkut', (string) ($manifest['identitas_alat_angkut'] ?? ''));
        $this->addManifestDataRow($table, '12', 'Waktu Mulai Pengangkutan', (string) ($manifest['waktu_mulai_pengangkutan'] ?? ''));
        $this->addManifestDataRow($table, '13', 'Waktu Selesai Pengangkutan', (string) ($manifest['waktu_selesai_pengangkutan'] ?? ''));
        $this->addManifestStatementRow(
            $table,
            'Pernyataan perusahaan Pengangkut Limbah B3: Dengan ini saya menyatakan bahwa Limbah B3 sesuai dengan data '
            .'yang disampaikan di atas diangkut menggunakan alat angkut yang berada dalam kondisi baik dan memenuhi '
            .'ketentuan yang tercantum dalam Peraturan Pemerintah Republik Indonesia.',
        );
        $this->addManifestDataRow($table, '14', 'Nama Penanggung Jawab', (string) ($manifest['penanggung_jawab_pengangkut'] ?? ''));
        $this->addManifestDataRow($table, '15', 'Jabatan', (string) ($manifest['jabatan_pj_pengangkut'] ?? ''));

        $this->addManifestSectionHeader($table, 'III. Informasi Tentang Penerima Limbah B3');
        $this->addManifestDataRow($table, '16', 'Nama dan Alamat Penerima Limbah B3', (string) ($manifest['penerima_nama_alamat'] ?? ''));
        $this->addManifestDataRow($table, '17', 'Nomor Telepon Penerima Limbah B3', (string) ($manifest['no_telepon_penerima'] ?? ''));
        $this->addManifestDataRow($table, '18', 'Jenis Pengelolaan Limbah B3', (string) ($manifest['jenis_pengelolaan'] ?? ''));
        $this->addManifestDataRow($table, '19', 'Jumlah Diterima', (string) ($manifest['jumlah_diterima_kg'] ?? ''));
        $this->addManifestDataRow($table, '20', 'Nama Penanggung Jawab', (string) ($manifest['penanggung_jawab_penerima'] ?? ''));
        $this->addManifestDataRow($table, '21', 'Jabatan', (string) ($manifest['jabatan_pj_penerima'] ?? ''));
    }

    private function addManifestSectionHeader(mixed $table, string $title): void
    {
        $table->addRow();
        $table->addCell(9000, [
            'gridSpan' => 3,
            'bgColor' => 'F3F4F6',
            'valign' => 'center',
        ])->addText($title, ['bold' => true, 'size' => 10]);
    }

    private function addManifestDataRow(
        mixed $table,
        string $no,
        string $judul,
        string $isian,
        bool $highlightNo = false,
    ): void {
        $table->addRow();
        $noCell = $table->addCell(900, ['valign' => 'center']);
        $noCell->addText($no, ['bold' => $highlightNo, 'size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(3600, ['valign' => 'center'])->addText($judul, ['size' => 10]);
        $this->addManifestIsianCell($table, $isian);
    }

    private function addManifestSubRow(mixed $table, string $judul, string $isian): void
    {
        $table->addRow();
        $table->addCell(900, ['valign' => 'center']);
        $table->addCell(3600, ['valign' => 'center'])->addText($judul, ['size' => 10]);
        $this->addManifestIsianCell($table, $isian);
    }

    private function addManifestIsianCell(mixed $table, string $isian): void
    {
        $cell = $table->addCell(4500, ['valign' => 'center']);

        foreach (preg_split("/\r\n|\n|\r/", $isian) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cell->addText($line, ['size' => 10]);
        }
    }

    private function addManifestStatementRow(mixed $table, string $text): void
    {
        $table->addRow();
        $cell = $table->addCell(9000, ['gridSpan' => 3, 'valign' => 'center']);
        $cell->addText($text, ['size' => 9, 'italic' => true]);
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function addFooterInfo(mixed $section, array $detail): void
    {
        $section->addTextBreak(1);
        $section->addText(
            sprintf(
                'Laporan ini digenerate pada %s oleh %s.',
                now()->format('d/m/Y H:i'),
                (string) ($detail['petugas'] ?? '-'),
            ),
            ['size' => 10, 'italic' => true],
        );
    }

    private function createTable(mixed $section, int $columnCount = 8): mixed
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
            $cell = $table->addCell(1200, ['bgColor' => 'D9D9D9', 'valign' => 'center']);
            $cell->addText($heading, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }
    }

    private function addEmptyTableRow(mixed $table, int $columnCount, string $message): void
    {
        $table->addRow();
        $table->addCell(800)->addText('1', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell(8200, ['gridSpan' => $columnCount - 1])->addText($message, ['size' => 10]);
    }

    private function addSectionHeading(mixed $section, string $text): void
    {
        $section->addText($text, ['bold' => true, 'size' => 12], ['spaceAfter' => 100, 'spaceBefore' => 100]);
    }

    private function saveDocument(PhpWord $phpWord, string $relativePath): void
    {
        $disk = Storage::disk('local');
        $directory = dirname($relativePath);

        if ($directory !== '.' && ! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'laporan-b3-');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $disk->put($relativePath, file_get_contents($tempPath) ?: '');

        @unlink($tempPath);
    }
}
