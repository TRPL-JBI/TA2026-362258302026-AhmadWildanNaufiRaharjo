<?php

namespace App\Services\Patroli;

use App\Models\PatroliLaporanPeriode;
use App\Models\User;
use App\Support\PatroliPeriode;
use Illuminate\Validation\ValidationException;

class PatroliLaporanPeriodeService
{
    /**
     * @return array<string, string>
     */
    public function statusMapFor(User $petugas): array
    {
        return PatroliLaporanPeriode::query()
            ->where('petugas_id', $petugas->id)
            ->get()
            ->mapWithKeys(fn (PatroliLaporanPeriode $row) => [
                $this->mapKey($row->jenis, PatroliPeriode::key($row->tahun, $row->caturwulan)) => $row->status,
            ])
            ->all();
    }

    public function statusFor(User $petugas, string $periodeKey, string $jenis): string
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        return PatroliLaporanPeriode::query()
            ->where('petugas_id', $petugas->id)
            ->where('tahun', $parsed['year'])
            ->where('caturwulan', $parsed['caturwulan'])
            ->where('jenis', $jenis)
            ->value('status') ?? PatroliLaporanPeriode::STATUS_BERLANGSUNG;
    }

    public function isSelesai(User $petugas, string $periodeKey, string $jenis): bool
    {
        return $this->statusFor($petugas, $periodeKey, $jenis) === PatroliLaporanPeriode::STATUS_SELESAI;
    }

    public function ensureBerlangsung(User $petugas, string $periodeKey, string $jenis): PatroliLaporanPeriode
    {
        $parsed = PatroliPeriode::parse($periodeKey);

        return PatroliLaporanPeriode::query()->firstOrCreate(
            [
                'petugas_id' => $petugas->id,
                'tahun' => $parsed['year'],
                'caturwulan' => $parsed['caturwulan'],
                'jenis' => $jenis,
            ],
            [
                'status' => PatroliLaporanPeriode::STATUS_BERLANGSUNG,
            ],
        );
    }

    public function markSelesai(User $petugas, string $periodeKey, string $jenis): PatroliLaporanPeriode
    {
        $record = $this->ensureBerlangsung($petugas, $periodeKey, $jenis);

        if ($record->status === PatroliLaporanPeriode::STATUS_SELESAI) {
            throw ValidationException::withMessages([
                'periode' => 'Laporan patroli untuk periode ini sudah ditandai selesai.',
            ]);
        }

        $record->update([
            'status' => PatroliLaporanPeriode::STATUS_SELESAI,
            'selesai_at' => now(),
        ]);

        return $record->fresh();
    }

    public function assertCanModify(User $petugas, string $periodeKey, string $jenis): void
    {
        if ($this->isSelesai($petugas, $periodeKey, $jenis)) {
            throw ValidationException::withMessages([
                'periode' => 'Laporan patroli untuk periode ini sudah selesai. Tidak dapat menambah atau mengubah data.',
            ]);
        }
    }

    public function mapKey(string $jenis, string $periodeKey): string
    {
        return $jenis.'-'.$periodeKey;
    }
}
