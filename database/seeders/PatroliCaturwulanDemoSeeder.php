<?php

namespace Database\Seeders;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\TindakLanjutInspeksi;
use App\Models\User;
use App\Services\LokasiQrCodeService;
use App\Services\Patroli\PatroliLaporanGenerateService;
use App\Services\Patroli\PatroliLaporanPeriodeService;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data demo patroli untuk membandingkan caturwulan sebelumnya vs periode berjalan.
 *
 * - Caturwulan I 2026 (Jan–Apr): temuan & APAR selesai + laporan ter-generate
 * - Caturwulan II 2026 (Mei–Agu): patroli berlangsung (sebagian lokasi/APAR)
 *
 * Jalankan: php artisan db:seed --class=PatroliCaturwulanDemoSeeder
 */
class PatroliCaturwulanDemoSeeder extends Seeder
{
    private const PERIODE_LALU = '2026-1';

    private const PERIODE_SEKARANG = '2026-2';

    public function run(): void
    {
        $petugas = User::query()->where('username', 'admin')->first();

        if ($petugas === null) {
            $this->command?->warn('User admin (Petugas K3LH) tidak ditemukan. Jalankan DatabaseSeeder terlebih dahulu.');

            return;
        }

        if (
            PatroliLaporanPeriode::query()
                ->where('petugas_id', $petugas->id)
                ->where('tahun', 2026)
                ->where('caturwulan', 1)
                ->where('jenis', PatroliLaporanPeriode::JENIS_TEMUAN)
                ->where('status', PatroliLaporanPeriode::STATUS_SELESAI)
                ->exists()
        ) {
            $this->command?->info('Demo patroli Caturwulan I 2026 sudah ada, dilewati.');

            return;
        }

        DB::transaction(function () use ($petugas) {
            $inventaris = $this->ensureInventaris($petugas);

            $this->seedPeriodeLalu($petugas, $inventaris);
            $this->seedPeriodeSekarang($petugas, $inventaris);
        });

        $this->command?->info('Demo patroli Caturwulan I & II 2026 berhasil dibuat untuk user admin.');
        $this->command?->line('  · Riwayat: pilih periode 2026-1 (selesai) vs 2026-2 (berlangsung)');
        $this->command?->line('  · Laporan: menu Laporan → filter Patroli');
    }

    /**
     * @return array{
     *     gedung: Lokasi,
     *     lab: Lokasi,
     *     checklist_gedung: MasterChecklist,
     *     checklist_lab: MasterChecklist,
     *     apars: list<Apar>
     * }
     */
    private function ensureInventaris(User $petugas): array
    {
        $gedung = Lokasi::query()->firstOrCreate(
            ['nama_lokasi' => 'Gedung Administrasi'],
            [
                'kode_lokasi' => LokasiQrCodeService::generateKodeLokasi('Gedung'),
                'jenis_lokasi' => 'Gedung',
                'deskripsi' => 'Kantor dan ruang rapat utama',
            ],
        );

        $lab = Lokasi::query()->firstOrCreate(
            ['nama_lokasi' => 'Laboratorium Kimia'],
            [
                'kode_lokasi' => LokasiQrCodeService::generateKodeLokasi('Laboratorium'),
                'jenis_lokasi' => 'Laboratorium',
                'deskripsi' => 'Praktikum kimia dasar',
            ],
        );

        $checklistGedung = MasterChecklist::query()->firstOrCreate(
            [
                'lokasi_id' => $gedung->id,
                'nama_checklist' => 'Checklist Keselamatan Gedung',
            ],
            [
                'dibuat_oleh_id' => $petugas->id,
                'jenis_pengelola' => 'Petugas K3LH',
                'status' => 'Aktif',
            ],
        );

        $checklistLab = MasterChecklist::query()->firstOrCreate(
            [
                'lokasi_id' => $lab->id,
                'nama_checklist' => 'Checklist Laboratorium Kimia',
            ],
            [
                'dibuat_oleh_id' => $petugas->id,
                'jenis_pengelola' => 'Kalab',
                'status' => 'Aktif',
            ],
        );

        $itemsGedung = $this->ensureItems($checklistGedung, [
            ['nama_item' => 'APAR siap pakai', 'probability' => 2, 'severity' => 3, 'skor_risiko' => 6, 'level_risiko' => 'Sedang'],
            ['nama_item' => 'Jalur evakuasi bebas', 'probability' => 3, 'severity' => 4, 'skor_risiko' => 12, 'level_risiko' => 'Tinggi'],
            ['nama_item' => 'Kabel listrik rapi', 'probability' => 4, 'severity' => 4, 'skor_risiko' => 16, 'level_risiko' => 'Sangat Tinggi'],
        ]);

        $itemsLab = $this->ensureItems($checklistLab, [
            ['nama_item' => 'Ventilasi laboratorium berfungsi', 'probability' => 2, 'severity' => 3, 'skor_risiko' => 6, 'level_risiko' => 'Sedang'],
            ['nama_item' => 'Tempat penyimpanan B3 tertutup', 'probability' => 3, 'severity' => 4, 'skor_risiko' => 12, 'level_risiko' => 'Tinggi'],
        ]);

        $apars = [];

        foreach ([
            [$gedung, 'APAR-GED-01'],
            [$gedung, 'APAR-GED-02'],
            [$lab, 'APAR-LAB-01'],
            [$lab, 'APAR-LAB-02'],
        ] as [$lokasi, $kode]) {
            $apars[] = Apar::query()->firstOrCreate(
                ['kode_apar' => $kode],
                [
                    'lokasi_id' => $lokasi->id,
                    'jenis_apar' => 'Powder',
                    'kapasitas_kg' => 6,
                    'tanggal_expired' => Carbon::create(2027, 6, 1),
                    'status_kondisi' => null,
                    'keterangan' => 'Unit demo patroli',
                ],
            );
        }

        return [
            'gedung' => $gedung,
            'lab' => $lab,
            'checklist_gedung' => $checklistGedung,
            'checklist_lab' => $checklistLab,
            'items_gedung' => $itemsGedung,
            'items_lab' => $itemsLab,
            'apars' => $apars,
        ];
    }

    /**
     * @param  list<array{nama_item: string, probability: int, severity: int, skor_risiko: int, level_risiko: string}>  $definitions
     * @return list<ItemChecklist>
     */
    private function ensureItems(MasterChecklist $checklist, array $definitions): array
    {
        $items = [];

        foreach ($definitions as $definition) {
            $items[] = ItemChecklist::query()->firstOrCreate(
                [
                    'master_checklist_id' => $checklist->id,
                    'nama_item' => $definition['nama_item'],
                ],
                [
                    'probability' => $definition['probability'],
                    'severity' => $definition['severity'],
                    'skor_risiko' => $definition['skor_risiko'],
                    'level_risiko' => $definition['level_risiko'],
                    'status' => 'Aktif',
                ],
            );
        }

        return $items;
    }

    /**
     * @param  array{
     *     gedung: Lokasi,
     *     lab: Lokasi,
     *     checklist_gedung: MasterChecklist,
     *     checklist_lab: MasterChecklist,
     *     items_gedung: list<ItemChecklist>,
     *     items_lab: list<ItemChecklist>,
     *     apars: list<Apar>
     * }  $inventaris
     */
    private function seedPeriodeLalu(User $petugas, array $inventaris): void
    {
        $tanggalGedung = Carbon::create(2026, 3, 12, 9, 30);
        $tanggalLab = Carbon::create(2026, 4, 5, 14, 0);

        $this->createInspeksi(
            $petugas,
            $inventaris['gedung'],
            $inventaris['checklist_gedung'],
            $tanggalGedung,
            [
                [$inventaris['items_gedung'][0], DetailInspeksi::STATUS_YA],
                [$inventaris['items_gedung'][1], DetailInspeksi::STATUS_YA],
                [$inventaris['items_gedung'][2], DetailInspeksi::STATUS_TIDAK, 'Kabel di pojok ruang rapat terkelupas', 'Ganti kabel dan pasang pelindung'],
            ],
        );

        $this->createInspeksi(
            $petugas,
            $inventaris['lab'],
            $inventaris['checklist_lab'],
            $tanggalLab,
            [
                [$inventaris['items_lab'][0], DetailInspeksi::STATUS_YA],
                [$inventaris['items_lab'][1], DetailInspeksi::STATUS_YA],
            ],
        );

        foreach ($inventaris['apars'] as $index => $apar) {
            $this->createPemeriksaanApar(
                $petugas,
                $apar,
                Carbon::create(2026, 2, 20, 10, 0)->addDays($index * 3),
                $index === 2 ? 'Tidak Tersegel' : 'Tersegel',
            );
        }

        $laporanPeriode = app(PatroliLaporanPeriodeService::class);
        $laporanGenerate = app(PatroliLaporanGenerateService::class);

        $laporanPeriode->markSelesai($petugas, self::PERIODE_LALU, PatroliLaporanPeriode::JENIS_TEMUAN);
        $laporanGenerate->generateTemuan($petugas, self::PERIODE_LALU);

        $laporanPeriode->markSelesai($petugas, self::PERIODE_LALU, PatroliLaporanPeriode::JENIS_APAR);
        $laporanGenerate->generateApar($petugas, self::PERIODE_LALU);
    }

    /**
     * @param  array{
     *     gedung: Lokasi,
     *     lab: Lokasi,
     *     checklist_gedung: MasterChecklist,
     *     checklist_lab: MasterChecklist,
     *     items_gedung: list<ItemChecklist>,
     *     items_lab: list<ItemChecklist>,
     *     apars: list<Apar>
     * }  $inventaris
     */
    private function seedPeriodeSekarang(User $petugas, array $inventaris): void
    {
        $periodeKey = self::PERIODE_SEKARANG;
        $laporanPeriode = app(PatroliLaporanPeriodeService::class);

        $this->createInspeksi(
            $petugas,
            $inventaris['gedung'],
            $inventaris['checklist_gedung'],
            Carbon::create(2026, 6, 8, 10, 0),
            [
                [$inventaris['items_gedung'][0], DetailInspeksi::STATUS_YA],
                [$inventaris['items_gedung'][1], DetailInspeksi::STATUS_TIDAK, 'Pintu darurat tertutup barang', 'Bersihkan jalur evakuasi'],
                [$inventaris['items_gedung'][2], DetailInspeksi::STATUS_YA],
            ],
        );

        $laporanPeriode->ensureBerlangsung(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        foreach (array_slice($inventaris['apars'], 0, 2) as $index => $apar) {
            $this->createPemeriksaanApar(
                $petugas,
                $apar,
                Carbon::create(2026, 6, 12, 11, 0)->addDays($index),
                'Tersegel',
            );
        }

        $laporanPeriode->ensureBerlangsung(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_APAR,
        );
    }

    /**
     * @param  list<array{0: ItemChecklist, 1: string, 2?: string, 3?: string}>  $answers
     */
    private function createInspeksi(
        User $petugas,
        Lokasi $lokasi,
        MasterChecklist $checklist,
        Carbon $tanggal,
        array $answers,
    ): InspeksiK3l {
        $itemSesuai = collect($answers)->where('1', DetailInspeksi::STATUS_YA)->count();
        $itemTidak = collect($answers)->where('1', DetailInspeksi::STATUS_TIDAK)->count();
        $totalItem = count($answers);

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasi->id,
            'master_checklist_id' => $checklist->id,
            'tanggal_inspeksi' => $tanggal,
            'total_item' => $totalItem,
            'item_sesuai' => $itemSesuai,
            'item_tidak_sesuai' => $itemTidak,
            'persentase_kepatuhan' => $totalItem > 0
                ? round(($itemSesuai / $totalItem) * 100, 2)
                : null,
        ]);

        foreach ($answers as $answer) {
            [$item, $status] = $answer;
            $analisa = $answer[2] ?? null;
            $rekomendasi = $answer[3] ?? null;

            $detail = DetailInspeksi::query()->create([
                'inspeksi_k3l_id' => $inspeksi->id,
                'item_checklist_id' => $item->id,
                'status' => $status,
                'analisa_risiko' => $status === DetailInspeksi::STATUS_TIDAK ? $analisa : null,
                'rekomendasi' => $status === DetailInspeksi::STATUS_TIDAK ? $rekomendasi : null,
                'skor_risiko_hasil' => $status === DetailInspeksi::STATUS_TIDAK ? $item->skor_risiko : null,
                'level_risiko_hasil' => $status === DetailInspeksi::STATUS_TIDAK ? $item->level_risiko : null,
            ]);

            if ($detail->isTemuanKritis()) {
                TindakLanjutInspeksi::query()->create([
                    'detail_inspeksi_id' => $detail->id,
                    'petugas_id' => $petugas->id,
                    'status_perbaikan' => 'Dalam Proses',
                ]);
            }
        }

        return $inspeksi;
    }

    private function createPemeriksaanApar(
        User $petugas,
        Apar $apar,
        Carbon $tanggal,
        string $kondisiSegel,
    ): PemeriksaanApar {
        $pemeriksaan = PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $apar->id,
            'tanggal_pemeriksaan' => $tanggal,
            'kondisi_tabung' => 'Tabung dalam kondisi baik, label terbaca jelas.',
            'kondisi_segel' => $kondisiSegel,
            'catatan' => 'Data demo seeder '.PatroliPeriode::displayLabel(PatroliPeriode::keyFromDate($tanggal)),
        ]);

        $apar->update([
            'status_kondisi' => $kondisiSegel === 'Tersegel'
                ? Apar::KONDISI_BAIK_TERSEGEL
                : Apar::KONDISI_TERBUKA,
        ]);

        return $pemeriksaan;
    }
}
