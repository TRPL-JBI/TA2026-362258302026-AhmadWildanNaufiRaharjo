<?php

namespace App\Services\Patroli;

use App\Models\Apar;
use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\User;
use App\Services\PhotoStorageService;
use App\Support\PatroliPeriode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatroliAparService
{
    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, list<UploadedFile>>  $fotoByAparId
     * @return array{count: int, deleted: int, periode: ?string}
     */
    public function store(User $petugas, array $rows, array $fotoByAparId = [], ?string $syncPeriodeKey = null): array
    {
        if ($rows === [] && $syncPeriodeKey === null) {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Tidak ada data pemeriksaan APAR untuk disimpan.',
            ]);
        }

        $periodeKey = $syncPeriodeKey ?? PatroliPeriode::keyFromDate(now());
        $this->laporanPeriodeService->assertCanModify(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_APAR,
        );

        $count = 0;
        $deleted = 0;
        $savedPeriode = null;
        $keptPemeriksaanIds = [];

        DB::transaction(function () use ($petugas, $rows, $fotoByAparId, $syncPeriodeKey, &$count, &$deleted, &$savedPeriode, &$keptPemeriksaanIds) {
            foreach ($rows as $row) {
                $saved = $this->storeOne($petugas, $row, $fotoByAparId);

                if ($saved !== null) {
                    $count++;
                    $keptPemeriksaanIds[] = $saved->id;
                    $savedPeriode ??= PatroliPeriode::keyFromDate($saved->tanggal_pemeriksaan);
                }
            }

            if ($syncPeriodeKey !== null) {
                $deleted = $this->deleteOrphanPemeriksaan($petugas, $syncPeriodeKey, $keptPemeriksaanIds);
                $savedPeriode ??= $syncPeriodeKey;
            }
        });

        if ($count === 0 && $deleted === 0) {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Tidak ada APAR baru yang disimpan. Unit ini mungkin sudah diperiksa pada caturwulan patroli yang sama.',
            ]);
        }

        $this->laporanPeriodeService->ensureBerlangsung(
            $petugas,
            $periodeKey,
            PatroliLaporanPeriode::JENIS_APAR,
        );

        return ['count' => $count, 'deleted' => $deleted, 'periode' => $savedPeriode];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, list<UploadedFile>>  $fotoByAparId
     */
    private function storeOne(User $petugas, array $row, array $fotoByAparId): ?PemeriksaanApar
    {
        $aparId = (int) ($row['apar_id'] ?? $row['id'] ?? 0);
        $kondisiTabung = trim((string) ($row['kondisi_tabung'] ?? $row['kondisiTabung'] ?? ''));
        $kondisiSegelRaw = (string) ($row['kondisi_segel'] ?? $row['kondisiSegel'] ?? '');

        if ($aparId <= 0) {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'ID APAR tidak valid.',
            ]);
        }

        if ($kondisiTabung === '') {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Kondisi tabung wajib diisi untuk setiap APAR.',
            ]);
        }

        if ($kondisiSegelRaw === '') {
            throw ValidationException::withMessages([
                'pemeriksaan' => 'Kondisi segel wajib dipilih untuk setiap APAR.',
            ]);
        }

        $fotoFiles = $fotoByAparId[$aparId] ?? [];

        $apar = Apar::query()->findOrFail($aparId);
        $kondisiSegel = PemeriksaanApar::segelFromForm($kondisiSegelRaw);

        $expiredUpdate = $row['tanggal_expired_update'] ?? $row['tanggalExpiredUpdate'] ?? null;
        $pemeriksaanId = (int) ($row['pemeriksaan_id'] ?? 0);

        $existing = $pemeriksaanId > 0
            ? PemeriksaanApar::query()
                ->where('petugas_id', $petugas->id)
                ->whereKey($pemeriksaanId)
                ->first()
            : null;

        if ($existing !== null) {
            if ($existing->apar_id !== $apar->id) {
                throw ValidationException::withMessages([
                    'pemeriksaan' => 'Data pemeriksaan APAR tidak sesuai dengan unit yang dipilih.',
                ]);
            }

            return $this->updateExisting($petugas, $apar, $existing, $row, $fotoFiles, $kondisiTabung, $kondisiSegel, $expiredUpdate);
        }

        $tanggalPemeriksaan = now();
        [$periodeStart, $periodeEnd] = PatroliPeriode::dateRangeForKey(PatroliPeriode::keyFromDate($tanggalPemeriksaan));

        if (PemeriksaanApar::query()
            ->where('petugas_id', $petugas->id)
            ->where('apar_id', $apar->id)
            ->whereBetween('tanggal_pemeriksaan', [$periodeStart, $periodeEnd])
            ->exists()) {
            return null;
        }

        $fotoPaths = $fotoFiles !== []
            ? $this->photoStorage->storePatroliPhotos(
                $fotoFiles,
                'patroli/apar/'.now()->format('Y/m'),
            )
            : [];

        $pemeriksaan = PemeriksaanApar::query()->create([
            'petugas_id' => $petugas->id,
            'apar_id' => $apar->id,
            'tanggal_pemeriksaan' => $tanggalPemeriksaan,
            'kondisi_tabung' => $kondisiTabung,
            'kondisi_segel' => $kondisiSegel,
            'tanggal_expired_update' => $expiredUpdate ?: null,
            'catatan' => isset($row['catatan']) ? trim((string) $row['catatan']) : null,
            'foto_path' => $fotoPaths !== [] ? $this->photoStorage->encodePaths($fotoPaths) : null,
        ]);

        $apar->update([
            'status_kondisi' => Apar::statusKondisiFromSegel($kondisiSegelRaw),
        ]);

        if ($expiredUpdate) {
            $apar->update([
                'tanggal_expired' => $expiredUpdate,
                'is_notified' => false,
            ]);
        }

        return $pemeriksaan;
    }

    /**
     * @param  list<UploadedFile>  $fotoFiles
     */
    private function updateExisting(
        User $petugas,
        Apar $apar,
        PemeriksaanApar $existing,
        array $row,
        array $fotoFiles,
        string $kondisiTabung,
        string $kondisiSegel,
        mixed $expiredUpdate,
    ): PemeriksaanApar {
        $newFotoPaths = $fotoFiles !== []
            ? $this->photoStorage->storePatroliPhotos(
                $fotoFiles,
                'patroli/apar/'.now()->format('Y/m'),
            )
            : null;

        if ($newFotoPaths !== null && $existing->foto_path !== null) {
            $this->photoStorage->deleteStored($existing->foto_path);
        }

        $existing->update([
            'kondisi_tabung' => $kondisiTabung,
            'kondisi_segel' => $kondisiSegel,
            'tanggal_expired_update' => $expiredUpdate ?: null,
            'catatan' => isset($row['catatan']) ? trim((string) $row['catatan']) : null,
            'foto_path' => $newFotoPaths !== null
                ? $this->photoStorage->encodePaths($newFotoPaths)
                : $existing->foto_path,
        ]);

        $apar->update([
            'status_kondisi' => Apar::statusKondisiFromSegel((string) ($row['kondisi_segel'] ?? $row['kondisiSegel'] ?? '')),
        ]);

        if ($expiredUpdate) {
            $apar->update([
                'tanggal_expired' => $expiredUpdate,
                'is_notified' => false,
            ]);
        }

        return $existing->fresh();
    }

    /**
     * Hapus pemeriksaan tersimpan yang tidak lagi ada di form lanjutkan.
     *
     * @param  list<int>  $keepIds
     */
    private function deleteOrphanPemeriksaan(User $petugas, string $periodeKey, array $keepIds): int
    {
        [$start, $end] = PatroliPeriode::dateRangeForKey($periodeKey);

        $query = PemeriksaanApar::query()
            ->where('petugas_id', $petugas->id)
            ->whereBetween('tanggal_pemeriksaan', [$start, $end]);

        if ($keepIds !== []) {
            $query->whereNotIn('id', $keepIds);
        }

        $orphans = $query->get();
        $deleted = 0;

        foreach ($orphans as $row) {
            $this->photoStorage->deleteStored($row->foto_path);
            $row->delete();
            $deleted++;
        }

        return $deleted;
    }
}
