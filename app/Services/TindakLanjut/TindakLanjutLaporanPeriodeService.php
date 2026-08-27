<?php

namespace App\Services\TindakLanjut;

use App\Models\TindakLanjutLaporanPeriode;
use App\Models\User;
use App\Support\PatroliPeriode;
use Illuminate\Validation\ValidationException;

class TindakLanjutLaporanPeriodeService
{
    public function statusFor(string $periodeKey): string
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        return TindakLanjutLaporanPeriode::query()
            ->where('tahun', $parsed['year'])
            ->where('caturwulan', $parsed['caturwulan'])
            ->value('status') ?? TindakLanjutLaporanPeriode::STATUS_BERLANGSUNG;
    }

    public function isSelesai(string $periodeKey): bool
    {
        return $this->statusFor($periodeKey) === TindakLanjutLaporanPeriode::STATUS_SELESAI;
    }

    public function ensureBerlangsung(string $periodeKey): TindakLanjutLaporanPeriode
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        return TindakLanjutLaporanPeriode::query()->firstOrCreate(
            [
                'tahun' => $parsed['year'],
                'caturwulan' => $parsed['caturwulan'],
            ],
            [
                'status' => TindakLanjutLaporanPeriode::STATUS_BERLANGSUNG,
            ],
        );
    }

    public function markSelesai(User $petugas, string $periodeKey, array $itemsSnapshot): TindakLanjutLaporanPeriode
    {
        $record = $this->ensureBerlangsung($periodeKey);

        if ($record->status === TindakLanjutLaporanPeriode::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'periode' => 'Periode tindak lanjut ini sudah ditandai selesai.',
            ]);
        }

        $record->update([
            'status' => TindakLanjutLaporanPeriode::STATUS_SELESAI,
            'selesai_by_id' => $petugas->id,
            'selesai_at' => now(),
            'items_snapshot' => $itemsSnapshot,
        ]);

        return $record->fresh();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function itemsSnapshot(string $periodeKey): ?array
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        $snapshot = TindakLanjutLaporanPeriode::query()
            ->where('tahun', $parsed['year'])
            ->where('caturwulan', $parsed['caturwulan'])
            ->where('status', TindakLanjutLaporanPeriode::STATUS_SELESAI)
            ->value('items_snapshot');

        if (! is_array($snapshot)) {
            return null;
        }

        return array_values($snapshot);
    }

    public function assertCanModify(string $periodeKey): void
    {
        if ($this->isSelesai($periodeKey)) {
            throw ValidationException::withMessages([
                'periode' => 'Periode tindak lanjut ini sudah ditutup. Data tidak dapat diubah.',
            ]);
        }
    }
}
