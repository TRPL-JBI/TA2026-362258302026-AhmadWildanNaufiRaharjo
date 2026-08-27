<?php

namespace App\Services\Patroli;

use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\TindakLanjutInspeksi;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Services\Laporan\PatroliReportGenerator;
use App\Services\PhotoStorageService;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatroliRiwayatService
{
    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
        private readonly LaporanRegistryService $laporanRegistryService,
        private readonly PatroliRiwayatOverviewService $overviewService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function markTemuanSelesai(User $petugas, string $periode): PatroliLaporanPeriode
    {
        $progress = $this->overviewService->overview($petugas, $periode)['temuan']['progress'] ?? [];
        $selesai = (int) ($progress['selesai'] ?? 0);
        $total = (int) ($progress['total'] ?? 0);

        if ($total < 1 || $selesai !== $total) {
            $sisa = max(0, $total - $selesai);

            throw ValidationException::withMessages([
                'periode' => $total < 1
                    ? 'Belum ada lokasi temuan bahaya yang dapat ditandai selesai.'
                    : "Semua lokasi temuan bahaya harus dicek sebelum menandai selesai. Masih tersisa {$sisa} lokasi.",
            ]);
        }

        return $this->laporanPeriodeService->markSelesai(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );
    }

    public function markAparSelesai(User $petugas, string $periode): PatroliLaporanPeriode
    {
        $progress = $this->overviewService->overview($petugas, $periode)['apar']['progress'] ?? [];
        $selesai = (int) ($progress['selesai'] ?? 0);
        $total = (int) ($progress['total'] ?? 0);

        if ($total < 1 || $selesai !== $total) {
            $sisa = max(0, $total - $selesai);

            throw ValidationException::withMessages([
                'periode' => $total < 1
                    ? 'Belum ada unit APAR yang dapat ditandai selesai.'
                    : "Semua unit APAR harus dicek sebelum menandai selesai. Masih tersisa {$sisa} unit.",
            ]);
        }

        return $this->laporanPeriodeService->markSelesai(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_APAR,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function detailTemuan(User $petugas, string $periode): array
    {
        $inspeksi = $this->inspeksiInPeriode($petugas, $periode);

        if ($inspeksi->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data inspeksi temuan untuk periode ini tidak ditemukan.',
            ]);
        }

        $rows = $inspeksi->map(function (InspeksiK3l $row) {
            $details = $row->details->map(fn (DetailInspeksi $detail) => [
                'nama_item' => $detail->itemChecklist?->nama_item ?? '-',
                'status' => $detail->status,
                'analisa_risiko' => $detail->analisa_risiko,
                'rekomendasi' => $detail->rekomendasi,
                'level_risiko' => $detail->level_risiko_hasil,
                'skor_risiko' => $detail->skor_risiko_hasil,
                'foto_paths' => $this->photoStorage->decodePaths($detail->foto_path),
            ])->values()->all();

            return [
                'id' => $row->id,
                'lokasi' => $row->lokasi?->nama_lokasi ?? '-',
                'checklist' => $row->masterChecklist?->nama_checklist ?? '-',
                'tanggal' => Carbon::parse($row->tanggal_inspeksi)->format('d/m/Y'),
                'waktu' => Carbon::parse($row->tanggal_inspeksi)->format('H:i'),
                'total_item' => $row->total_item,
                'item_sesuai' => $row->item_sesuai,
                'item_tidak_sesuai' => $row->item_tidak_sesuai,
                'persentase_kepatuhan' => $row->persentase_kepatuhan,
                'details' => $details,
            ];
        })->values()->all();

        return [
            'periode' => $periode,
            'periode_label' => PatroliPeriode::displayLabel($periode),
            'nama_laporan' => PatroliPeriode::laporanPatroliTitle($periode),
            'status' => $this->laporanPeriodeService->statusFor($petugas, $periode, PatroliLaporanPeriode::JENIS_TEMUAN),
            'lokasi_count' => count($rows),
            'temuan_count' => (int) collect($rows)->sum('item_tidak_sesuai'),
            'inspeksi' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detailApar(User $petugas, string $periode): array
    {
        $pemeriksaan = $this->pemeriksaanInPeriode($petugas, $periode);

        if ($pemeriksaan->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data pemeriksaan APAR untuk periode ini tidak ditemukan.',
            ]);
        }

        $rows = $pemeriksaan->map(function (PemeriksaanApar $row) {
            $apar = $row->apar;

            return [
                'id' => $row->id,
                'kode_apar' => $apar?->kode_apar ?? '-',
                'lokasi' => $apar?->lokasi?->nama_lokasi ?? '-',
                'keterangan_apar' => $apar?->keterangan,
                'jenis_kapasitas' => $apar?->jenisKapasitasLabel() ?? '-',
                'tanggal_expired_year' => $apar?->tanggal_expired?->format('Y'),
                'tanggal' => Carbon::parse($row->tanggal_pemeriksaan)->format('d/m/Y'),
                'waktu' => Carbon::parse($row->tanggal_pemeriksaan)->format('H:i'),
                'kondisi_tabung' => $row->kondisi_tabung,
                'kondisi_segel' => $row->kondisi_segel,
                'catatan' => $row->catatan,
                'foto_paths' => $this->photoStorage->decodePaths($row->foto_path),
                'foto_urls' => array_map(
                    fn (string $path) => $this->photoStorage->publicUrl($path),
                    $this->photoStorage->decodePaths($row->foto_path),
                ),
            ];
        })->values()->all();

        return [
            'periode' => $periode,
            'periode_label' => PatroliPeriode::displayLabel($periode),
            'nama_laporan' => PatroliPeriode::laporanAparTitle($periode),
            'status' => $this->laporanPeriodeService->statusFor($petugas, $periode, PatroliLaporanPeriode::JENIS_APAR),
            'apar_count' => count($rows),
            'pemeriksaan' => $rows,
        ];
    }

    /**
     * Muat inspeksi tersimpan ke format draft (untuk lanjutkan + tambah lokasi baru).
     *
     * @return list<array<string, mixed>>
     */
    public function sectionsForContinue(User $petugas, string $periode, bool $viewOnly = false): array
    {
        if (! $viewOnly) {
            $this->laporanPeriodeService->assertCanModify(
                $petugas,
                $periode,
                PatroliLaporanPeriode::JENIS_TEMUAN,
            );
        }

        return $this->inspeksiInPeriode($petugas, $periode)
            ->map(function (InspeksiK3l $inspeksi) {
                $items = $inspeksi->details->map(function (DetailInspeksi $detail) {
                    $item = $detail->itemChecklist;

                    return [
                        'id' => $detail->item_checklist_id,
                        'namaItem' => $item?->nama_item ?? '-',
                        'probability' => (int) ($item?->probability ?? 0),
                        'severity' => (int) ($item?->severity ?? 0),
                        'status' => $detail->status === DetailInspeksi::STATUS_YA ? 'ya' : 'tidak',
                        'analisaRisiko' => $detail->analisa_risiko ?? '',
                        'rekomendasi' => $detail->rekomendasi ?? '',
                        'fotoDokumentasi' => $this->photoStorage->fotoEntriesFromStored($detail->foto_path),
                    ];
                })->values()->all();

                return [
                    'id' => $inspeksi->lokasi_id,
                    'inspeksi_id' => $inspeksi->id,
                    'nama' => $inspeksi->lokasi?->nama_lokasi ?? '-',
                    'namaChecklist' => $inspeksi->masterChecklist?->nama_checklist ?? '-',
                    'master_checklist_id' => $inspeksi->master_checklist_id,
                    'expanded' => true,
                    'persisted' => true,
                    'items' => $items,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Hapus seluruh riwayat inspeksi temuan pada satu periode (milik petugas).
     */
    public function destroyTemuanByPeriode(User $petugas, string $periode): int
    {
        $inspeksi = $this->inspeksiInPeriode($petugas, $periode);

        if ($inspeksi->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data inspeksi temuan untuk periode ini tidak ditemukan.',
            ]);
        }

        $deleted = 0;

        DB::transaction(function () use ($inspeksi, $periode, $petugas, &$deleted) {
            foreach ($inspeksi as $row) {
                $detailIds = $row->details->pluck('id')->all();

                if ($detailIds !== []) {
                    TindakLanjutInspeksi::query()->whereIn('detail_inspeksi_id', $detailIds)->delete();
                }

                foreach ($row->details as $detail) {
                    $this->photoStorage->deleteStored($detail->foto_path);
                }

                $row->details()->delete();
                $row->delete();
                $deleted++;
            }

            PatroliLaporanPeriode::query()
                ->where('petugas_id', $petugas->id)
                ->where('tahun', PatroliPeriode::parse($periode)['year'])
                ->where('caturwulan', PatroliPeriode::parse($periode)['caturwulan'])
                ->where('jenis', PatroliLaporanPeriode::JENIS_TEMUAN)
                ->delete();

            $this->laporanRegistryService->deleteDocx(
                $petugas,
                PatroliReportGenerator::jenisLaporanForPatroli(PatroliLaporanPeriode::JENIS_TEMUAN),
                PatroliReportGenerator::periodeLabel($periode),
            );
        });

        return $deleted;
    }

    /**
     * Hapus seluruh riwayat pemeriksaan APAR pada satu periode (milik petugas).
     */
    public function destroyAparByPeriode(User $petugas, string $periode): int
    {
        $rows = $this->pemeriksaanInPeriode($petugas, $periode);

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data pemeriksaan APAR untuk periode ini tidak ditemukan.',
            ]);
        }

        DB::transaction(function () use ($rows, $periode, $petugas) {
            foreach ($rows as $row) {
                $this->photoStorage->deleteStored($row->foto_path);
                $row->delete();
            }

            PatroliLaporanPeriode::query()
                ->where('petugas_id', $petugas->id)
                ->where('tahun', PatroliPeriode::parse($periode)['year'])
                ->where('caturwulan', PatroliPeriode::parse($periode)['caturwulan'])
                ->where('jenis', PatroliLaporanPeriode::JENIS_APAR)
                ->delete();

            $this->laporanRegistryService->deleteDocx(
                $petugas,
                PatroliReportGenerator::jenisLaporanForPatroli(PatroliLaporanPeriode::JENIS_APAR),
                PatroliReportGenerator::periodeLabel($periode),
            );
        });

        return $rows->count();
    }

    /**
     * @return Collection<int, InspeksiK3l>
     */
    private function inspeksiInPeriode(User $petugas, string $periode): Collection
    {
        [$start, $end] = PatroliPeriode::dateRangeForKey($periode);

        return InspeksiK3l::query()
            ->where('petugas_id', $petugas->id)
            ->whereBetween('tanggal_inspeksi', [$start, $end])
            ->with([
                'lokasi:id,nama_lokasi',
                'masterChecklist:id,nama_checklist',
                'details.itemChecklist:id,nama_item,probability,severity,skor_risiko,level_risiko',
            ])
            ->orderBy('tanggal_inspeksi')
            ->get();
    }

    /**
     * @return Collection<int, PemeriksaanApar>
     */
    private function pemeriksaanInPeriode(User $petugas, string $periode): Collection
    {
        [$start, $end] = PatroliPeriode::dateRangeForKey($periode);

        return PemeriksaanApar::query()
            ->where('petugas_id', $petugas->id)
            ->whereBetween('tanggal_pemeriksaan', [$start, $end])
            ->with(['apar.lokasi:id,nama_lokasi'])
            ->orderBy('tanggal_pemeriksaan')
            ->get();
    }
}
