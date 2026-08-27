<?php

namespace App\Services\Pemantauan;

use App\Models\JenisLimbahB3;
use App\Models\LaporanGenerated;
use App\Models\LaporanLimbahB3;
use App\Models\LogbookLimbahB3;
use App\Models\ManifestLimbahB3;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\B3Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemantauanB3Service
{
    public function __construct(
        private readonly LaporanRegistryService $laporanRegistryService,
        private readonly PemantauanB3LaporanGenerateService $laporanGenerateService,
    ) {}
    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndex(): array
    {
        return LaporanLimbahB3::query()
            ->withCount(['jenisLimbah', 'logbook', 'manifest'])
            ->orderByDesc('tahun')
            ->orderByDesc('semester')
            ->get()
            ->map(fn (LaporanLimbahB3 $row) => $this->serializeListItem($row))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForEdit(LaporanLimbahB3 $laporan): array
    {
        $laporan->load(['jenisLimbah', 'logbook', 'manifest']);

        $semester = $laporan->semester;
        $bulanNames = B3Semester::semesterToBulanMap()[$semester] ?? B3Semester::semesterToBulanMap()[1];

        $logbookByBulan = $laporan->logbook->groupBy('bulan');

        $logbookBulanList = [];
        foreach ($bulanNames as $nama) {
            $bulanNumber = B3Semester::bulanNumberFromName($nama);
            $entries = $logbookByBulan->get($bulanNumber, collect());

            $logbookBulanList[] = [
                'nama' => $nama,
                'entries' => $entries->map(fn (LogbookLimbahB3 $row) => [
                    'id' => $row->id,
                    'tanggal_masuk' => $row->tanggal_masuk?->format('Y-m-d') ?? '',
                    'tanggal_keluar' => $row->tanggal_keluar?->format('Y-m-d') ?? '',
                    'jenis_limbah' => $row->jenis_limbah,
                    'sumber_limbah' => $row->sumber_limbah,
                    'jumlah_masuk_kg' => (string) $row->jumlah_masuk_kg,
                    'jumlah_keluar_kg' => $row->jumlah_keluar_kg !== null ? (string) $row->jumlah_keluar_kg : '',
                    'pengemasan' => $row->pengemasan ?? '',
                ])->values()->all(),
            ];
        }

        return [
            'id' => $laporan->id,
            'semester' => $laporan->semester,
            'tahun' => (string) $laporan->tahun,
            'status' => $laporan->status,
            'jenisList' => $laporan->jenisLimbah->map(fn (JenisLimbahB3 $row) => [
                'id' => $row->id,
                'nama_limbah' => $row->nama_limbah,
                'kode_limbah' => $row->kode_limbah,
                'sumber_limbah' => $row->sumber_limbah,
                'karakteristik' => $row->karakteristik,
                'pengemasan' => $row->pengemasan,
                'masa_simpan_hari' => (string) $row->masa_simpan_hari,
            ])->values()->all(),
            'logbookBulanList' => $logbookBulanList,
            'manifestList' => $laporan->manifest->map(fn (ManifestLimbahB3 $row) => $this->serializeManifest($row))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(User $user, array $payload): LaporanLimbahB3
    {
        $semester = (int) $payload['semester'];
        $tahun = (int) $payload['tahun'];

        $this->assertPeriodeAvailable($semester, $tahun);

        return DB::transaction(function () use ($user, $payload, $semester, $tahun) {
            $laporan = LaporanLimbahB3::query()->create([
                'petugas_id' => $user->id,
                'semester' => $semester,
                'tahun' => $tahun,
                'status' => B3Semester::STATUS_BERLANGSUNG,
            ]);

            $this->syncJenis($laporan, $payload['jenis_list'] ?? []);
            $this->syncLogbook($laporan, $payload['logbook_bulan_list'] ?? []);
            $this->syncManifest($laporan, $payload['manifest_list'] ?? []);

            return $laporan->fresh(['jenisLimbah', 'logbook', 'manifest']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(LaporanLimbahB3 $laporan, array $payload): LaporanLimbahB3
    {
        $semester = (int) $payload['semester'];
        $tahun = (int) $payload['tahun'];

        $this->assertPeriodeAvailable($semester, $tahun, $laporan->id);

        return DB::transaction(function () use ($laporan, $payload, $semester, $tahun) {
            $laporan->update([
                'semester' => $semester,
                'tahun' => $tahun,
            ]);

            $laporan->jenisLimbah()->delete();
            $laporan->logbook()->delete();
            $laporan->manifest()->delete();

            $this->syncJenis($laporan, $payload['jenis_list'] ?? []);
            $this->syncLogbook($laporan, $payload['logbook_bulan_list'] ?? []);
            $this->syncManifest($laporan, $payload['manifest_list'] ?? []);

            return $laporan->fresh(['jenisLimbah', 'logbook', 'manifest']);
        });
    }

    public function markSelesai(LaporanLimbahB3 $laporan): LaporanLimbahB3
    {
        $laporan->update([
            'status' => B3Semester::STATUS_SELESAI,
        ]);

        $laporan = $laporan->fresh(['jenisLimbah', 'logbook', 'manifest', 'petugas']);

        $petugas = $laporan->petugas ?? User::query()->find($laporan->petugas_id);

        if ($petugas !== null) {
            $this->laporanGenerateService->generate($petugas, $laporan);
        }

        return $laporan;
    }

    public function destroy(LaporanLimbahB3 $laporan): void
    {
        $petugas = User::query()->find($laporan->petugas_id);

        DB::transaction(function () use ($laporan): void {
            $laporan->jenisLimbah()->delete();
            $laporan->logbook()->delete();
            $laporan->manifest()->delete();
            $laporan->delete();
        });

        if ($petugas !== null) {
            $this->laporanRegistryService->deleteDocx(
                $petugas,
                LaporanGenerated::JENIS_B3,
                B3Semester::labelWithYear($laporan->semester, $laporan->tahun),
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $jenisList
     */
    private function syncJenis(LaporanLimbahB3 $laporan, array $jenisList): void
    {
        foreach ($jenisList as $jenis) {
            if (! is_array($jenis)) {
                continue;
            }

            JenisLimbahB3::query()->create([
                'laporan_limbah_b3_id' => $laporan->id,
                'nama_limbah' => trim((string) ($jenis['nama_limbah'] ?? '')),
                'kode_limbah' => trim((string) ($jenis['kode_limbah'] ?? '')),
                'sumber_limbah' => trim((string) ($jenis['sumber_limbah'] ?? '')),
                'karakteristik' => trim((string) ($jenis['karakteristik'] ?? '')),
                'pengemasan' => trim((string) ($jenis['pengemasan'] ?? '')),
                'masa_simpan_hari' => (int) ($jenis['masa_simpan_hari'] ?? 0),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $bulanList
     */
    private function syncLogbook(LaporanLimbahB3 $laporan, array $bulanList): void
    {
        foreach ($bulanList as $bulan) {
            $bulanNumber = B3Semester::bulanNumberFromName((string) ($bulan['nama'] ?? ''));

            if ($bulanNumber === 0) {
                continue;
            }

            foreach ($bulan['entries'] ?? [] as $entry) {
                if (! $this->isLogbookEntryFilled($entry)) {
                    continue;
                }

                LogbookLimbahB3::query()->create([
                    'laporan_limbah_b3_id' => $laporan->id,
                    'bulan' => $bulanNumber,
                    'tanggal_masuk' => $entry['tanggal_masuk'],
                    'tanggal_keluar' => $this->nullableString($entry['tanggal_keluar'] ?? null),
                    'jenis_limbah' => trim((string) ($entry['jenis_limbah'] ?? '')),
                    'sumber_limbah' => trim((string) ($entry['sumber_limbah'] ?? '')),
                    'jumlah_masuk_kg' => $entry['jumlah_masuk_kg'],
                    'jumlah_keluar_kg' => $this->nullableDecimal($entry['jumlah_keluar_kg'] ?? null),
                    'pengemasan' => $this->nullableString($entry['pengemasan'] ?? null),
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $manifestList
     */
    private function syncManifest(LaporanLimbahB3 $laporan, array $manifestList): void
    {
        foreach ($manifestList as $manifest) {
            if (! $this->isManifestEntryFilled($manifest)) {
                continue;
            }

            ManifestLimbahB3::query()->create([
                'laporan_limbah_b3_id' => $laporan->id,
                'nomor_manifest' => trim((string) ($manifest['nomor_manifest'] ?? '')),
                'tanggal_manifest' => $manifest['tanggal_manifest'],
                'nama_pengirim' => trim((string) ($manifest['nama_pengirim'] ?? '')),
                'alamat_pengirim' => trim((string) ($manifest['alamat_pengirim'] ?? '')),
                'nama_fasilitas_penyimpanan' => $this->nullableString($manifest['nama_fasilitas_penyimpanan'] ?? null),
                'penanggung_jawab_pengirim' => $this->nullableString($manifest['penanggung_jawab_pengirim'] ?? null),
                'jabatan_pj_pengirim' => $this->nullableString($manifest['jabatan_pj_pengirim'] ?? null),
                'kode_limbah' => trim((string) ($manifest['kode_limbah'] ?? '')),
                'nama_limbah' => trim((string) ($manifest['nama_limbah'] ?? '')),
                'nama_teknik' => $this->nullableString($manifest['nama_teknik'] ?? null),
                'periode_limbah_mulai' => $this->nullableString($manifest['periode_limbah_mulai'] ?? null),
                'periode_limbah_selesai' => $this->nullableString($manifest['periode_limbah_selesai'] ?? null),
                'karakteristik_limbah' => trim((string) ($manifest['karakteristik_limbah'] ?? '')),
                'jenis_kemasan' => trim((string) ($manifest['jenis_kemasan'] ?? '')),
                'jumlah_kemasan' => (int) ($manifest['jumlah_kemasan'] ?? 0),
                'jumlah_limbah_ton' => $manifest['jumlah_limbah_ton'],
                'keterangan_tambahan' => $this->nullableString($manifest['keterangan_tambahan'] ?? null),
                'tujuan_pengangkutan' => trim((string) ($manifest['tujuan_pengangkutan'] ?? '')),
                'nama_pengangkut' => trim((string) ($manifest['nama_pengangkut'] ?? '')),
                'alamat_pengangkut' => trim((string) ($manifest['alamat_pengangkut'] ?? '')),
                'no_telepon_darurat' => $this->nullableString($manifest['no_telepon_darurat'] ?? null),
                'jumlah_ril' => $this->nullableInt($manifest['jumlah_ril'] ?? null),
                'identitas_alat_angkut' => $this->nullableString($manifest['identitas_alat_angkut'] ?? null),
                'waktu_mulai_pengangkutan' => $this->nullableString($manifest['waktu_mulai_pengangkutan'] ?? null),
                'waktu_selesai_pengangkutan' => $this->nullableString($manifest['waktu_selesai_pengangkutan'] ?? null),
                'penanggung_jawab_pengangkut' => $this->nullableString($manifest['penanggung_jawab_pengangkut'] ?? null),
                'jabatan_pj_pengangkut' => $this->nullableString($manifest['jabatan_pj_pengangkut'] ?? null),
                'nama_penerima' => trim((string) ($manifest['nama_penerima'] ?? '')),
                'alamat_penerima' => trim((string) ($manifest['alamat_penerima'] ?? '')),
                'no_telepon_penerima' => $this->nullableString($manifest['no_telepon_penerima'] ?? null),
                'jenis_pengelolaan' => trim((string) ($manifest['jenis_pengelolaan'] ?? '')),
                'jumlah_diterima_kg' => $this->nullableDecimal($manifest['jumlah_diterima_kg'] ?? null),
                'penanggung_jawab_penerima' => $this->nullableString($manifest['penanggung_jawab_penerima'] ?? null),
                'jabatan_pj_penerima' => $this->nullableString($manifest['jabatan_pj_penerima'] ?? null),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeManifest(ManifestLimbahB3 $row): array
    {
        return [
            'id' => $row->id,
            'nomor_manifest' => $row->nomor_manifest,
            'tanggal_manifest' => $row->tanggal_manifest?->format('Y-m-d') ?? '',
            'nama_pengirim' => $row->nama_pengirim,
            'alamat_pengirim' => $row->alamat_pengirim,
            'nama_fasilitas_penyimpanan' => $row->nama_fasilitas_penyimpanan ?? '',
            'penanggung_jawab_pengirim' => $row->penanggung_jawab_pengirim ?? '',
            'jabatan_pj_pengirim' => $row->jabatan_pj_pengirim ?? '',
            'kode_limbah' => $row->kode_limbah,
            'nama_limbah' => $row->nama_limbah,
            'nama_teknik' => $row->nama_teknik ?? '',
            'periode_limbah_mulai' => $row->periode_limbah_mulai?->format('Y-m-d') ?? '',
            'periode_limbah_selesai' => $row->periode_limbah_selesai?->format('Y-m-d') ?? '',
            'karakteristik_limbah' => $row->karakteristik_limbah,
            'jenis_kemasan' => $row->jenis_kemasan,
            'jumlah_kemasan' => (string) $row->jumlah_kemasan,
            'jumlah_limbah_ton' => (string) $row->jumlah_limbah_ton,
            'keterangan_tambahan' => $row->keterangan_tambahan ?? '',
            'tujuan_pengangkutan' => $row->tujuan_pengangkutan,
            'nama_pengangkut' => $row->nama_pengangkut,
            'alamat_pengangkut' => $row->alamat_pengangkut,
            'no_telepon_darurat' => $row->no_telepon_darurat ?? '',
            'jumlah_ril' => $row->jumlah_ril !== null ? (string) $row->jumlah_ril : '',
            'identitas_alat_angkut' => $row->identitas_alat_angkut ?? '',
            'waktu_mulai_pengangkutan' => $row->waktu_mulai_pengangkutan?->format('Y-m-d\TH:i') ?? '',
            'waktu_selesai_pengangkutan' => $row->waktu_selesai_pengangkutan?->format('Y-m-d\TH:i') ?? '',
            'penanggung_jawab_pengangkut' => $row->penanggung_jawab_pengangkut ?? '',
            'jabatan_pj_pengangkut' => $row->jabatan_pj_pengangkut ?? '',
            'nama_penerima' => $row->nama_penerima,
            'alamat_penerima' => $row->alamat_penerima,
            'no_telepon_penerima' => $row->no_telepon_penerima ?? '',
            'jenis_pengelolaan' => $row->jenis_pengelolaan,
            'jumlah_diterima_kg' => $row->jumlah_diterima_kg !== null ? (string) $row->jumlah_diterima_kg : '',
            'penanggung_jawab_penerima' => $row->penanggung_jawab_penerima ?? '',
            'jabatan_pj_penerima' => $row->jabatan_pj_penerima ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function isManifestEntryFilled(array $entry): bool
    {
        foreach ([
            'nomor_manifest',
            'tanggal_manifest',
            'nama_pengirim',
            'alamat_pengirim',
            'nama_fasilitas_penyimpanan',
            'penanggung_jawab_pengirim',
            'jabatan_pj_pengirim',
            'kode_limbah',
            'nama_limbah',
            'nama_teknik',
            'periode_limbah_mulai',
            'periode_limbah_selesai',
            'karakteristik_limbah',
            'jenis_kemasan',
            'jumlah_kemasan',
            'jumlah_limbah_ton',
            'keterangan_tambahan',
            'tujuan_pengangkutan',
            'nama_pengangkut',
            'alamat_pengangkut',
            'no_telepon_darurat',
            'jumlah_ril',
            'identitas_alat_angkut',
            'waktu_mulai_pengangkutan',
            'waktu_selesai_pengangkutan',
            'penanggung_jawab_pengangkut',
            'jabatan_pj_pengangkut',
            'nama_penerima',
            'alamat_penerima',
            'no_telepon_penerima',
            'jenis_pengelolaan',
            'jumlah_diterima_kg',
            'penanggung_jawab_penerima',
            'jabatan_pj_penerima',
        ] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function isLogbookEntryFilled(array $entry): bool
    {
        foreach (['tanggal_masuk', 'tanggal_keluar', 'jenis_limbah', 'sumber_limbah', 'jumlah_masuk_kg', 'jumlah_keluar_kg', 'pengemasan'] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function assertPeriodeAvailable(int $semester, int $tahun, ?int $ignoreId = null): void
    {
        if (! in_array($semester, B3Semester::semesterOptions(), true)) {
            throw ValidationException::withMessages([
                'semester' => 'Semester tidak valid.',
            ]);
        }

        $exists = LaporanLimbahB3::query()
            ->where('semester', $semester)
            ->where('tahun', $tahun)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'semester' => 'Laporan untuk semester dan tahun ini sudah ada.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListItem(LaporanLimbahB3 $row): array
    {
        $jenisCount = (int) ($row->jenis_limbah_count ?? $row->jenisLimbah()->count());
        $logbookCount = (int) ($row->logbook_count ?? $row->logbook()->count());
        $manifestCount = (int) ($row->manifest_count ?? $row->manifest()->count());
        $isSelesai = $row->status === B3Semester::STATUS_SELESAI;

        $progress = $isSelesai
            ? 'Periode ditutup - data tetap dapat diedit'
            : sprintf('%d jenis limbah, %d logbook, %d manifest', $jenisCount, $logbookCount, $manifestCount);

        $tanggal = $row->updated_at ?? $row->created_at;

        return [
            'id' => $row->id,
            'semester' => $row->semester,
            'tahun' => (string) $row->tahun,
            'status' => $row->status,
            'progress' => $progress,
            'jenisCount' => $jenisCount,
            'logbookCount' => $logbookCount,
            'manifestCount' => $manifestCount,
            'nama_laporan' => B3Semester::labelWithYear($row->semester, $row->tahun),
            'tanggal' => $tanggal?->format('d/m/Y') ?? '-',
            'jumlah' => $progress,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $text = trim(str_replace(',', '.', (string) $value));

        return $text === '' ? null : $text;
    }

    private function nullableInt(mixed $value): ?int
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return (int) $text;
    }
}
