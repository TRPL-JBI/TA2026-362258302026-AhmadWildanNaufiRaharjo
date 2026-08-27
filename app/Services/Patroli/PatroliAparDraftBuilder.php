<?php

namespace App\Services\Patroli;

use App\Models\PatroliLaporanPeriode;
use App\Models\PemeriksaanApar;
use App\Models\User;
use App\Services\PhotoStorageService;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PatroliAparDraftBuilder
{
    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function lokasiSectionsForContinue(User $petugas, string $periode, bool $viewOnly = false): array
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            throw ValidationException::withMessages([
                'periode' => 'Periode patroli tidak valid.',
            ]);
        }

        if (! $viewOnly) {
            $this->laporanPeriodeService->assertCanModify(
                $petugas,
                $periode,
                PatroliLaporanPeriode::JENIS_APAR,
            );
        }

        [$start, $end] = PatroliPeriode::dateRangeForKey($periode);

        $grouped = PemeriksaanApar::query()
            ->where('petugas_id', $petugas->id)
            ->whereBetween('tanggal_pemeriksaan', [$start, $end])
            ->with(['apar.lokasi:id,nama_lokasi'])
            ->orderBy('tanggal_pemeriksaan')
            ->get()
            ->groupBy(fn (PemeriksaanApar $row) => $row->apar?->lokasi?->nama_lokasi ?? 'Lokasi APAR');

        if ($grouped->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data pemeriksaan APAR untuk periode ini tidak ditemukan.',
            ]);
        }

        return $grouped
            ->map(function (Collection $rows, string $lokasiNama) {
                return [
                    'id' => $lokasiNama,
                    'nama' => $lokasiNama,
                    'expanded' => false,
                    'saved' => true,
                    'aparList' => $rows->map(function (PemeriksaanApar $row) {
                        $apar = $row->apar;
                        $tanggalPemeriksaan = Carbon::parse($row->tanggal_pemeriksaan);
                        $tanggalExpired = $apar?->tanggal_expired;

                        return [
                            'id' => $apar?->id,
                            'apar_id' => $apar?->id,
                            'pemeriksaan_id' => $row->id,
                            'kodeApar' => $apar?->kode_apar ?? '-',
                            'lokasiApar' => $apar?->lokasi?->nama_lokasi ?? '-',
                            'jenisKapasitas' => $apar?->jenisKapasitasLabel() ?? '-',
                            'tanggalPemeriksaan' => $tanggalPemeriksaan->translatedFormat('d F Y'),
                            'tanggalExpired' => $tanggalExpired?->translatedFormat('d F Y') ?? '-',
                            'tanggalExpiredIso' => $tanggalExpired?->format('Y-m-d'),
                            'kondisiTabung' => $row->kondisi_tabung,
                            'kondisiSegel' => $row->kondisi_segel === 'Tersegel' ? 'tersegel' : 'tidak-tersegel',
                            'fotoKondisi' => $this->photoStorage->fotoEntriesFromStored($row->foto_path),
                            'persisted' => true,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
