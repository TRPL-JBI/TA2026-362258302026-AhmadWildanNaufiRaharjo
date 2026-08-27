<?php

namespace App\Services\Patroli;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\Lokasi;
use App\Models\MasterChecklist;
use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\User;
use App\Support\ChecklistTemuanAccess;
use App\Support\PatroliLokasiAccess;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PatroliRiwayatOverviewService
{
    public function __construct(
        private readonly PatroliChecklistResolver $checklistResolver,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function periodeOptions(User $petugas): array
    {
        $current = PatroliPeriode::keyFromDate(now());

        $fromInspeksi = InspeksiK3l::query()
            ->where('petugas_id', $petugas->id)
            ->pluck('tanggal_inspeksi')
            ->map(fn ($date) => PatroliPeriode::keyFromDate(Carbon::parse($date)));

        $fromApar = PemeriksaanApar::query()
            ->where('petugas_id', $petugas->id)
            ->pluck('tanggal_pemeriksaan')
            ->map(fn ($date) => PatroliPeriode::keyFromDate(Carbon::parse($date)));

        $fromRegistry = PatroliLaporanPeriode::query()
            ->where('petugas_id', $petugas->id)
            ->get()
            ->map(fn (PatroliLaporanPeriode $row) => PatroliPeriode::key($row->tahun, $row->caturwulan));

        return collect([$current])
            ->merge($fromInspeksi)
            ->merge($fromApar)
            ->merge($fromRegistry)
            ->unique()
            ->sortByDesc(fn (string $key) => PatroliPeriode::parse($key)['year'] * 10 + PatroliPeriode::parse($key)['caturwulan'])
            ->values()
            ->map(fn (string $key) => [
                'value' => $key,
                'label' => PatroliPeriode::displayLabel($key),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(User $petugas, string $periode): array
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            throw ValidationException::withMessages([
                'periode' => 'Periode patroli tidak valid.',
            ]);
        }

        return [
            'periode' => $periode,
            'periode_label' => PatroliPeriode::displayLabel($periode),
            'rentang_tanggal' => PatroliPeriode::rentangTanggal($periode),
            'temuan' => $this->temuanSection($petugas, $periode),
            'apar' => $this->aparSection($petugas, $periode),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function temuanSection(User $petugas, string $periode): array
    {
        $status = $this->laporanPeriodeService->statusFor(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        $canModify = $status === PatroliLaporanPeriode::STATUS_BERLANGSUNG;
        $historical = $this->isHistoricalPeriode($periode);
        $periodEnd = $this->periodEnd($periode);
        $inspeksiByLokasi = $this->inspeksiInPeriode($petugas, $periode)->keyBy('lokasi_id');

        $lokasi = [];

        foreach (PatroliLokasiAccess::allLokasi() as $row) {
            $built = $this->buildLokasiRow(
                $row,
                $inspeksiByLokasi,
                $periode,
                PatroliLokasiAccess::canCreateChecklist($petugas, $row),
                $canModify,
                $historical,
                $periodEnd,
            );

            if ($built !== null) {
                $lokasi[] = $built;
            }
        }

        usort($lokasi, fn (array $a, array $b) => strcmp($a['nama'], $b['nama']));

        $selesaiCount = count(array_filter($lokasi, fn (array $row) => $row['status'] === 'selesai'));
        $totalLokasi = count($lokasi);

        return [
            'status' => $status,
            'can_modify' => $canModify,
            'nama_laporan' => PatroliPeriode::laporanPatroliTitle($periode),
            'progress' => [
                'selesai' => $selesaiCount,
                'total' => $totalLokasi,
                'persen' => $totalLokasi > 0 ? (int) round(($selesaiCount / $totalLokasi) * 100) : 0,
                'temuan_count' => (int) collect($lokasi)->sum('item_tidak_sesuai'),
                'lengkap' => $totalLokasi > 0 && $selesaiCount === $totalLokasi,
            ],
            'lokasi' => $lokasi,
            'finish_url' => route('patroli.riwayat.temuan.selesai', ['periode' => $periode], false),
            'scan_url' => route('patroli.scan', ['type' => 'temuan', 'continue' => 1, 'periode' => $periode], false),
            'view_inspeksi_url' => $selesaiCount > 0
                ? route('patroli.temuan', ['continue_periode' => $periode], false)
                : null,
            'checklist_options' => $this->checklistOptionsFor($petugas),
            'lokasi_tanpa_checklist' => collect($lokasi)
                ->filter(fn (array $row) => ($row['belum_siap_jenis'] ?? null) === 'checklist' && $row['can_buat_checklist'])
                ->map(fn (array $row) => ['id' => $row['lokasi_id'], 'label' => $row['nama']])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aparSection(User $petugas, string $periode): array
    {
        $status = $this->laporanPeriodeService->statusFor(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_APAR,
        );

        $canModify = $status === PatroliLaporanPeriode::STATUS_BERLANGSUNG;
        $historical = $this->isHistoricalPeriode($periode);
        $periodEnd = $this->periodEnd($periode);
        $pemeriksaanByApar = $this->pemeriksaanInPeriode($petugas, $periode)->keyBy('apar_id');

        $aparQuery = Apar::query()
            ->with('lokasi:id,nama_lokasi')
            ->orderBy('kode_apar');

        if ($historical) {
            $aparQuery->where('created_at', '<=', $periodEnd);
        }

        $units = $aparQuery
            ->get()
            ->map(fn (Apar $apar) => $this->buildAparRow($apar, $pemeriksaanByApar))
            ->values()
            ->all();

        $selesaiCount = count(array_filter($units, fn (array $row) => $row['status'] === 'selesai'));
        $total = count($units);

        return [
            'status' => $status,
            'can_modify' => $canModify,
            'nama_laporan' => PatroliPeriode::laporanAparTitle($periode),
            'progress' => [
                'selesai' => $selesaiCount,
                'total' => $total,
                'persen' => $total > 0 ? (int) round(($selesaiCount / $total) * 100) : 0,
                'lengkap' => $total > 0 && $selesaiCount === $total,
            ],
            'units' => $units,
            'finish_url' => route('patroli.riwayat.apar.selesai', ['periode' => $periode], false),
            'scan_url' => route('patroli.scan', ['type' => 'apar', 'continue' => 1, 'periode' => $periode], false),
            'view_apar_url' => $selesaiCount > 0
                ? route('patroli.apar', ['continue_periode' => $periode], false)
                : null,
        ];
    }

    /**
     * @param  Collection<int, InspeksiK3l>  $inspeksiByLokasi
     * @return array<string, mixed>|null
     */
    private function buildLokasiRow(
        Lokasi $lokasi,
        Collection $inspeksiByLokasi,
        string $periode,
        bool $canBuatChecklist = false,
        bool $canModify = true,
        bool $historical = false,
        ?Carbon $periodEnd = null,
    ): ?array {
        $periodLocked = ! $canModify;
        $inspeksi = $inspeksiByLokasi->get($lokasi->id);

        if ($historical && $inspeksi === null && $lokasi->created_at?->gt($periodEnd)) {
            return null;
        }

        $checklist = $historical && $periodEnd !== null
            ? $this->checklistResolver->masterChecklistForAsOf($lokasi, $periodEnd)
            : $this->checklistResolver->masterChecklistFor($lokasi);

        $itemQuery = $checklist === null
            ? null
            : ItemChecklist::query()->where('master_checklist_id', $checklist->id);

        if ($itemQuery !== null && $historical && $periodEnd !== null) {
            $itemQuery->where('created_at', '<=', $periodEnd);
        }

        $totalItemCount = $itemQuery?->count() ?? 0;
        $activeItemCount = $checklist === null
            ? 0
            : (clone $itemQuery)->where('status', 'Aktif')->count();

        if ($historical && $inspeksi === null) {
            if ($checklist === null || $totalItemCount === 0) {
                return null;
            }
        }

        $base = [
            'lokasi_id' => $lokasi->id,
            'nama' => $lokasi->nama_lokasi,
            'jenis_lokasi' => $lokasi->jenis_lokasi,
            'can_buat_checklist' => $canBuatChecklist && $canModify,
            'inspect_url' => route('patroli.temuan', [
                'q' => json_encode(['type' => 'lokasi', 'id' => $lokasi->id], JSON_UNESCAPED_UNICODE),
                'periode' => $periode,
            ], false),
            'item_tidak_sesuai' => 0,
        ];

        // 'nama' => $base['nama'],
        if ($inspeksi !== null) {
            return [
                ...$base,
                'status' => 'selesai',
                'inspeksi_id' => $inspeksi->id,
                'checklist_id' => $checklist?->id,
                'nama_checklist' => $inspeksi->masterChecklist?->nama_checklist ?? $checklist?->nama_checklist,
                'item_count' => $inspeksi->total_item,
                'item_sesuai' => $inspeksi->item_sesuai,
                'item_tidak_sesuai' => $inspeksi->item_tidak_sesuai,
                'persentase_kepatuhan' => $inspeksi->persentase_kepatuhan,
                'tanggal' => Carbon::parse($inspeksi->tanggal_inspeksi)->format('d/m/Y H:i'),
                'checklist_items' => $this->checklistItemsFromInspeksi($inspeksi),
                'checklist_live' => false,
            ];
        }

        if ($checklist === null) {
            return [
                ...$base,
                'status' => 'belum_siap',
                'belum_siap_jenis' => 'checklist',
                'status_label' => 'Belum ada checklist',
                'checklist_id' => null,
                'nama_checklist' => null,
                'item_count' => 0,
                'checklist_items' => [],
                'checklist_live' => false,
            ];
        }

        if ($totalItemCount === 0) {
            if ($periodLocked) {
                return $this->belumRowForLokasi($base, $checklist, 0, [], false);
            }

            return [
                ...$base,
                'status' => 'belum_siap',
                'belum_siap_jenis' => 'item',
                'status_label' => 'Belum ada item temuan',
                'checklist_id' => $checklist->id,
                'nama_checklist' => $checklist->nama_checklist,
                'item_count' => 0,
                'checklist_items' => [],
                'checklist_live' => false,
            ];
        }

        if ($periodLocked) {
            return $this->belumRowForLokasi($base, $checklist, 0, [], false);
        }

        return $this->belumRowForLokasi(
            $base,
            $checklist,
            $activeItemCount,
            $this->checklistItemsLive($checklist, $historical ? $periodEnd : null),
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  list<array<string, mixed>>  $checklistItems
     * @return array<string, mixed>
     */
    private function belumRowForLokasi(
        array $base,
        MasterChecklist $checklist,
        int $activeItemCount,
        array $checklistItems,
        bool $checklistLive,
    ): array {
        return [
            ...$base,
            'status' => 'belum',
            'checklist_id' => $checklist->id,
            'nama_checklist' => $checklist->nama_checklist,
            'item_count' => $activeItemCount,
            'total_item_count' => count($checklistItems),
            'checklist_items' => $checklistItems,
            'checklist_live' => $checklistLive,
        ];
    }

    /**
     * Item checklist aktif saat ini — hanya untuk periode berlangsung.
     *
     * @return list<array<string, mixed>>
     */
    private function checklistItemsLive(MasterChecklist $checklist, ?Carbon $asOf = null): array
    {
        $query = ItemChecklist::query()
            ->where('master_checklist_id', $checklist->id);

        if ($asOf !== null) {
            $query->where('created_at', '<=', $asOf);
        }

        return $query
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(fn (ItemChecklist $item) => [
                'id' => $item->id,
                'nama_item' => $item->nama_item,
                'status' => $item->status,
                'aktif' => $item->status === 'Aktif',
                'level_risiko' => $item->level_risiko,
                'hasil_inspeksi' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * Snapshot item dari inspeksi tersimpan — tidak ikut berubah saat master checklist diupdate.
     *
     * @return list<array<string, mixed>>
     */
    private function checklistItemsFromInspeksi(InspeksiK3l $inspeksi): array
    {
        return $inspeksi->details
            ->sortBy(fn (DetailInspeksi $detail) => $detail->itemChecklist?->urutan ?? $detail->id)
            ->map(function (DetailInspeksi $detail) {
                $item = $detail->itemChecklist;

                return [
                    'id' => $detail->item_checklist_id,
                    'nama_item' => $item?->nama_item ?? '-',
                    'status' => $item?->status ?? 'Nonaktif',
                    'aktif' => ($item?->status ?? '') === 'Aktif',
                    'level_risiko' => $item?->level_risiko ?? $detail->level_risiko_hasil,
                    'hasil_inspeksi' => $detail->status,
                ];
            })
            ->values()
            ->all();
    }

    private function isHistoricalPeriode(string $periode): bool
    {
        return PatroliPeriode::keyFromDate(now()) !== $periode;
    }

    private function periodEnd(string $periode): Carbon
    {
        return PatroliPeriode::dateRangeForKey($periode)[1];
    }

    public function toggleItemStatus(User $petugas, string $periode, ItemChecklist $itemChecklist): ItemChecklist
    {
        $this->laporanPeriodeService->assertCanModify(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        $itemChecklist->load('masterChecklist.lokasi');
        PatroliLokasiAccess::assertCanManageChecklistInPatroli($petugas, $itemChecklist->masterChecklist);

        $itemChecklist->update([
            'status' => $itemChecklist->status === 'Aktif' ? 'Nonaktif' : 'Aktif',
        ]);

        return $itemChecklist->fresh();
    }

    /**
     * @param  Collection<int, PemeriksaanApar>  $pemeriksaanByApar
     * @return array<string, mixed>
     */
    private function buildAparRow(Apar $apar, Collection $pemeriksaanByApar): array
    {
        $base = [
            'apar_id' => $apar->id,
            'kode_apar' => $apar->kode_apar,
            'nama' => $apar->kode_apar,
            'lokasi' => $apar->lokasi?->nama_lokasi ?? '-',
            'jenis_kapasitas' => $apar->jenisKapasitasLabel(),
        ];

        $pemeriksaan = $pemeriksaanByApar->get($apar->id);

        if ($pemeriksaan === null) {
            return [
                ...$base,
                'status' => 'belum',
            ];
        }

        return [
            ...$base,
            'status' => 'selesai',
            'tanggal' => Carbon::parse($pemeriksaan->tanggal_pemeriksaan)->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function checklistOptionsFor(User $petugas): array
    {
        return PatroliLokasiAccess::checklistQueryForPatroli($petugas)
            ->orderBy('nama_checklist')
            ->get()
            ->map(fn (MasterChecklist $row) => [
                'id' => $row->id,
                'label' => ($row->lokasi?->nama_lokasi ?? '-').' · '.$row->nama_checklist,
                'lokasi_id' => $row->lokasi_id,
            ])
            ->values()
            ->all();
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
                'details.itemChecklist:id,nama_item,status,level_risiko,urutan',
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
            ->orderBy('tanggal_pemeriksaan')
            ->get();
    }

    public function storeChecklist(User $petugas, string $periode, array $validated): MasterChecklist
    {
        $this->laporanPeriodeService->assertCanModify(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        PatroliLokasiAccess::resolveForChecklistCreation($petugas, (int) $validated['lokasi_id']);

        $lokasi = Lokasi::query()->findOrFail($validated['lokasi_id']);

        return MasterChecklist::query()->create([
            'nama_checklist' => $validated['nama_checklist'],
            'lokasi_id' => $validated['lokasi_id'],
            'dibuat_oleh_id' => $petugas->id,
            'jenis_pengelola' => PatroliLokasiAccess::pengelolaFor($lokasi),
            'status' => 'Aktif',
        ]);
    }

    public function storeItem(User $petugas, string $periode, MasterChecklist $masterChecklist, array $validated): ItemChecklist
    {
        $this->laporanPeriodeService->assertCanModify(
            $petugas,
            $periode,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        PatroliLokasiAccess::assertCanManageChecklistInPatroli($petugas, $masterChecklist);

        $nextUrutan = (int) $masterChecklist->items()->max('urutan') + 1;

        return $masterChecklist->items()->create([
            'nama_item' => $validated['nama_item'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'probability' => $validated['probability'],
            'severity' => $validated['severity'],
            'urutan' => $nextUrutan,
            'status' => 'Aktif',
        ]);
    }
}
