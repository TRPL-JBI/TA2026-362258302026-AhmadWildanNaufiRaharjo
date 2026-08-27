<?php

namespace App\Services\Patroli;

use App\Models\DetailInspeksi;
use App\Models\InspeksiK3l;
use App\Models\ItemChecklist;
use App\Models\MasterChecklist;
use App\Models\PatroliLaporanPeriode;
use App\Models\TindakLanjutInspeksi;
use App\Models\User;
use App\Services\PhotoStorageService;
use App\Support\PatroliPeriode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatroliInspeksiService
{
    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    /**
     * Simpan satu atau beberapa lokasi checklist dalam satu transaksi.
     *
     * @param  list<array<string, mixed>>  $sections
     * @param  array<int, UploadedFile>  $fotoByItemId
     * @return array{inspeksi_count: int, temuan_count: int, tindak_lanjut_count: int}
     */
    public function store(User $petugas, array $sections, array $fotoByItemId = []): array
    {
        if ($sections === []) {
            throw ValidationException::withMessages([
                'sections' => 'Tidak ada data inspeksi untuk disimpan.',
            ]);
        }

        $periodeKey = PatroliPeriode::keyFromDate(now());
        $this->laporanPeriodeService->assertCanModify(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        $totals = [
            'inspeksi_count' => 0,
            'temuan_count' => 0,
            'tindak_lanjut_count' => 0,
        ];

        $hasAnsweredInput = false;
        $skippedDuplicates = 0;

        DB::transaction(function () use ($petugas, $sections, $fotoByItemId, &$totals, &$hasAnsweredInput, &$skippedDuplicates) {
            foreach ($sections as $section) {
                $result = $this->storeSection($petugas, $section, $fotoByItemId);
                $totals['inspeksi_count'] += $result['inspeksi_count'];
                $totals['temuan_count'] += $result['temuan_count'];
                $totals['tindak_lanjut_count'] += $result['tindak_lanjut_count'];

                if ($result['has_answered_input'] ?? false) {
                    $hasAnsweredInput = true;
                }

                if ($result['skipped_duplicate'] ?? false) {
                    $skippedDuplicates += 1;
                }
            }
        });

        if ($totals['inspeksi_count'] === 0) {
            if ($hasAnsweredInput && $skippedDuplicates > 0) {
                throw ValidationException::withMessages([
                    'sections' => 'Lokasi yang dipilih sudah diinspeksi pada caturwulan ini. Gunakan Lanjutkan dari riwayat atau pilih lokasi lain.',
                ]);
            }

            throw ValidationException::withMessages([
                'sections' => 'Minimal satu item harus ditandai Ya atau Tidak sebelum menyimpan.',
            ]);
        }

        $this->laporanPeriodeService->ensureBerlangsung(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_TEMUAN,
        );

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $section
     * @param  array<int, UploadedFile>  $fotoByItemId
     * @return array{inspeksi_count: int, temuan_count: int, tindak_lanjut_count: int, skipped_duplicate?: bool, has_answered_input?: bool}
     */
    private function storeSection(User $petugas, array $section, array $fotoByItemId): array
    {
        $lokasiId = (int) ($section['lokasi_id'] ?? $section['id'] ?? 0);
        $masterChecklistId = (int) ($section['master_checklist_id'] ?? 0);
        $items = $section['items'] ?? [];

        if ($lokasiId <= 0 || $masterChecklistId <= 0 || ! is_array($items)) {
            throw ValidationException::withMessages([
                'sections' => 'Data lokasi atau checklist tidak valid.',
            ]);
        }

        $checklist = MasterChecklist::query()
            ->whereKey($masterChecklistId)
            ->where('lokasi_id', $lokasiId)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $activeItemIds = ItemChecklist::query()
            ->where('master_checklist_id', $checklist->id)
            ->where('status', 'Aktif')
            ->pluck('id')
            ->all();

        $totalItem = count($activeItemIds);
        $inspeksiId = (int) ($section['inspeksi_id'] ?? 0);

        $existing = $inspeksiId > 0
            ? InspeksiK3l::query()
                ->where('petugas_id', $petugas->id)
                ->whereKey($inspeksiId)
                ->with('details')
                ->first()
            : null;

        $existingFotoByItemId = $existing?->details
            ->keyBy('item_checklist_id')
            ->map(fn (DetailInspeksi $detail) => $detail->foto_path)
            ->all() ?? [];

        $answered = $this->normalizeAnswers(
            $items,
            $activeItemIds,
            $fotoByItemId,
            $existing !== null ? $existingFotoByItemId : [],
        );

        if ($answered === []) {
            return [
                'inspeksi_count' => 0,
                'temuan_count' => 0,
                'tindak_lanjut_count' => 0,
                'has_answered_input' => false,
                'skipped_duplicate' => false,
            ];
        }

        $itemSesuai = collect($answered)->where('status', 'ya')->count();
        $itemTidak = collect($answered)->where('status', 'tidak')->count();
        $persentase = $totalItem > 0
            ? round(($itemSesuai / $totalItem) * 100, 2)
            : null;

        if ($existing !== null) {
            if ($existing->lokasi_id !== $lokasiId || $existing->master_checklist_id !== $masterChecklistId) {
                throw ValidationException::withMessages([
                    'sections' => 'Data inspeksi tidak sesuai dengan lokasi atau checklist yang dipilih.',
                ]);
            }

            return $this->replaceSectionDetails(
                $petugas,
                $existing,
                $answered,
                $totalItem,
                $itemSesuai,
                $itemTidak,
                $persentase,
            );
        }

        $tanggalInspeksi = now();
        [$periodeStart, $periodeEnd] = PatroliPeriode::dateRangeForKey(PatroliPeriode::keyFromDate($tanggalInspeksi));

        if (InspeksiK3l::query()
            ->where('petugas_id', $petugas->id)
            ->where('lokasi_id', $lokasiId)
            ->whereBetween('tanggal_inspeksi', [$periodeStart, $periodeEnd])
            ->exists()) {
            return [
                'inspeksi_count' => 0,
                'temuan_count' => 0,
                'tindak_lanjut_count' => 0,
                'has_answered_input' => true,
                'skipped_duplicate' => true,
            ];
        }

        $inspeksi = InspeksiK3l::query()->create([
            'petugas_id' => $petugas->id,
            'lokasi_id' => $lokasiId,
            'master_checklist_id' => $masterChecklistId,
            'tanggal_inspeksi' => $tanggalInspeksi,
            'total_item' => $totalItem,
            'item_sesuai' => $itemSesuai,
            'item_tidak_sesuai' => $itemTidak,
            'persentase_kepatuhan' => $persentase,
        ]);

        $temuanCount = 0;
        $tindakLanjutCount = 0;

        foreach ($answered as $row) {
            $item = ItemChecklist::query()->findOrFail($row['item_checklist_id']);
            $statusDb = $row['status'] === 'ya' ? DetailInspeksi::STATUS_YA : DetailInspeksi::STATUS_TIDAK;

            $detail = DetailInspeksi::query()->create([
                'inspeksi_k3l_id' => $inspeksi->id,
                'item_checklist_id' => $item->id,
                'status' => $statusDb,
                'analisa_risiko' => $row['status'] === 'tidak' ? $row['analisa_risiko'] : null,
                'rekomendasi' => $row['status'] === 'tidak' ? $row['rekomendasi'] : null,
                'foto_path' => $row['foto_path'] ?? null,
                'catatan' => $row['catatan'] ?? null,
                'skor_risiko_hasil' => $statusDb === DetailInspeksi::STATUS_TIDAK ? $item->skor_risiko : null,
                'level_risiko_hasil' => $statusDb === DetailInspeksi::STATUS_TIDAK ? $item->level_risiko : null,
            ]);

            if ($statusDb === DetailInspeksi::STATUS_TIDAK) {
                $temuanCount++;

                if ($detail->isTemuanKritis()) {
                    TindakLanjutInspeksi::query()->create([
                        'detail_inspeksi_id' => $detail->id,
                        'petugas_id' => $petugas->id,
                        'status_perbaikan' => 'Dalam Proses',
                    ]);
                    $tindakLanjutCount++;
                }
            }
        }

        return [
            'inspeksi_count' => 1,
            'temuan_count' => $temuanCount,
            'tindak_lanjut_count' => $tindakLanjutCount,
            'has_answered_input' => true,
            'skipped_duplicate' => false,
        ];
    }

    /**
     * @param  list<array{item_checklist_id: int, status: string, analisa_risiko: ?string, rekomendasi: ?string, foto_path: ?string, catatan: ?string}>  $answered
     * @return array{inspeksi_count: int, temuan_count: int, tindak_lanjut_count: int, has_answered_input: bool, skipped_duplicate: bool}
     */
    private function replaceSectionDetails(
        User $petugas,
        InspeksiK3l $inspeksi,
        array $answered,
        int $totalItem,
        int $itemSesuai,
        int $itemTidak,
        ?float $persentase,
    ): array {
        $replacedFotoPaths = [];

        foreach ($inspeksi->details as $detail) {
            TindakLanjutInspeksi::query()->where('detail_inspeksi_id', $detail->id)->delete();
            $replacedFotoPaths[] = $detail->foto_path;
            $detail->delete();
        }

        $inspeksi->update([
            'total_item' => $totalItem,
            'item_sesuai' => $itemSesuai,
            'item_tidak_sesuai' => $itemTidak,
            'persentase_kepatuhan' => $persentase,
        ]);

        $temuanCount = 0;
        $tindakLanjutCount = 0;
        $keptFotoPaths = [];

        foreach ($answered as $row) {
            $item = ItemChecklist::query()->findOrFail($row['item_checklist_id']);
            $statusDb = $row['status'] === 'ya' ? DetailInspeksi::STATUS_YA : DetailInspeksi::STATUS_TIDAK;

            $detail = DetailInspeksi::query()->create([
                'inspeksi_k3l_id' => $inspeksi->id,
                'item_checklist_id' => $item->id,
                'status' => $statusDb,
                'analisa_risiko' => $row['status'] === 'tidak' ? $row['analisa_risiko'] : null,
                'rekomendasi' => $row['status'] === 'tidak' ? $row['rekomendasi'] : null,
                'foto_path' => $row['foto_path'] ?? null,
                'catatan' => $row['catatan'] ?? null,
                'skor_risiko_hasil' => $statusDb === DetailInspeksi::STATUS_TIDAK ? $item->skor_risiko : null,
                'level_risiko_hasil' => $statusDb === DetailInspeksi::STATUS_TIDAK ? $item->level_risiko : null,
            ]);

            if ($row['foto_path'] !== null) {
                $keptFotoPaths[] = $row['foto_path'];
            }

            if ($statusDb === DetailInspeksi::STATUS_TIDAK) {
                $temuanCount++;

                if ($detail->isTemuanKritis()) {
                    TindakLanjutInspeksi::query()->create([
                        'detail_inspeksi_id' => $detail->id,
                        'petugas_id' => $petugas->id,
                        'status_perbaikan' => 'Dalam Proses',
                    ]);
                    $tindakLanjutCount++;
                }
            }
        }

        foreach ($replacedFotoPaths as $path) {
            if ($path !== null && ! in_array($path, $keptFotoPaths, true)) {
                $this->photoStorage->deleteStored($path);
            }
        }

        return [
            'inspeksi_count' => 1,
            'temuan_count' => $temuanCount,
            'tindak_lanjut_count' => $tindakLanjutCount,
            'has_answered_input' => true,
            'skipped_duplicate' => false,
        ];
    }

    /**
     * @param  list<int>  $activeItemIds
     * @param  array<int, UploadedFile>  $fotoByItemId
     * @param  array<int, string|null>  $existingFotoByItemId
     * @return list<array{item_checklist_id: int, status: string, analisa_risiko: ?string, rekomendasi: ?string, foto_path: ?string, catatan: ?string}>
     */
    private function normalizeAnswers(
        array $items,
        array $activeItemIds,
        array $fotoByItemId,
        array $existingFotoByItemId = [],
    ): array {
        $answered = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemId = (int) ($item['item_checklist_id'] ?? $item['id'] ?? 0);
            $status = strtolower((string) ($item['status'] ?? 'belum'));

            if ($itemId <= 0 || ! in_array($itemId, $activeItemIds, true)) {
                continue;
            }

            if (! in_array($status, ['ya', 'tidak'], true)) {
                continue;
            }

            $fotoPath = null;

            if ($status === 'tidak') {
                $analisa = trim((string) ($item['analisa_risiko'] ?? $item['analisaRisiko'] ?? ''));
                $rekomendasi = trim((string) ($item['rekomendasi'] ?? ''));

                if ($analisa === '' || $rekomendasi === '') {
                    throw ValidationException::withMessages([
                        'sections' => 'Analisa risiko dan rekomendasi wajib diisi untuk setiap item yang tidak sesuai.',
                    ]);
                }

                $fotoFile = $fotoByItemId[$itemId] ?? null;

                if ($fotoFile instanceof UploadedFile) {
                    $fotoPath = $this->photoStorage->storePatroliPhoto(
                        $fotoFile,
                        'patroli/temuan/'.now()->format('Y/m'),
                    );
                } elseif (($existingFotoByItemId[$itemId] ?? null) !== null) {
                    $fotoPath = $existingFotoByItemId[$itemId];
                } else {
                    throw ValidationException::withMessages([
                        'foto_item' => 'Foto dokumentasi wajib diunggah untuk setiap item yang tidak sesuai.',
                    ]);
                }
            }

            $answered[] = [
                'item_checklist_id' => $itemId,
                'status' => $status,
                'analisa_risiko' => $status === 'tidak'
                    ? trim((string) ($item['analisa_risiko'] ?? $item['analisaRisiko'] ?? ''))
                    : null,
                'rekomendasi' => $status === 'tidak'
                    ? trim((string) ($item['rekomendasi'] ?? ''))
                    : null,
                'foto_path' => $fotoPath,
                'catatan' => isset($item['catatan']) ? trim((string) $item['catatan']) : null,
            ];
        }

        return $answered;
    }
}
