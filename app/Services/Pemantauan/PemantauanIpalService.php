<?php

namespace App\Services\Pemantauan;

use App\Models\DampakLingkunganIpal;
use App\Models\DetailIpalHarian;
use App\Models\LaporanGenerated;
use App\Models\LaporanIpal;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\IpalTriwulan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemantauanIpalService
{
    public function __construct(
        private readonly PemantauanIpalLaporanGenerateService $laporanGenerateService,
        private readonly LaporanRegistryService $laporanRegistryService,
    ) {}
    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndex(): array
    {
        return LaporanIpal::query()
            ->orderByDesc('tahun')
            ->orderByDesc('triwulan')
            ->get()
            ->map(fn (LaporanIpal $row) => $this->serializeListItem($row))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForEdit(LaporanIpal $laporan): array
    {
        $laporan->load(['detailHarian', 'dampakLingkungan']);

        $triwulanKey = IpalTriwulan::keyFromNumber($laporan->triwulan);
        $bulanNames = IpalTriwulan::triwulanToBulanMap()[$triwulanKey] ?? [];

        $detailsByBulan = $laporan->detailHarian
            ->groupBy('bulan');

        $bulanList = [];
        foreach ($bulanNames as $nama) {
            $bulanNumber = IpalTriwulan::bulanNumberFromName($nama);
            $catatanRows = $detailsByBulan->get($bulanNumber, collect());

            $bulanList[] = [
                'nama' => $nama,
                'catatan' => $catatanRows->map(fn (DetailIpalHarian $detail) => [
                    'id' => $detail->id,
                    'tanggal' => $detail->tanggal_sampling?->format('Y-m-d') ?? '',
                    'debitIn' => (string) $detail->debit_input_m3,
                    'debitOut' => (string) $detail->debit_output_m3,
                    'pH' => $detail->ph !== null ? (string) $detail->ph : '',
                    'suhu' => $detail->suhu_celcius !== null ? (string) $detail->suhu_celcius : '',
                ])->values()->all(),
            ];
        }

        $dampak = $laporan->dampakLingkungan;

        return [
            'id' => $laporan->id,
            'triwulanKey' => $triwulanKey,
            'tahun' => (string) $laporan->tahun,
            'status' => $laporan->status,
            'bulanList' => $bulanList,
            'evaluasi' => [
                'jenisDampak' => $dampak?->jenis_dampak ?? '',
                'sumberDampak' => $dampak?->sumber_dampak ?? '',
                'parameterPemantauan' => $dampak?->parameter_pemantauan ?? '',
                'tolakUkur' => $dampak?->tolak_ukur ?? '',
                'lokasiPengelolaan' => $dampak?->lokasi_pengelolaan ?? '',
                'evaluasiHasil' => $dampak?->evaluasi_hasil ?? $laporan->evaluasi_kinerja ?? '',
                'tindakanPerbaikan' => $dampak?->tindakan_perbaikan ?? '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(User $user, array $payload): LaporanIpal
    {
        $triwulan = IpalTriwulan::numberFromKey((string) $payload['triwulan_key']);
        $tahun = (int) $payload['tahun'];

        $this->assertPeriodeAvailable($triwulan, $tahun);

        return DB::transaction(function () use ($user, $payload, $triwulan, $tahun) {
            $laporan = LaporanIpal::query()->create([
                'petugas_id' => $user->id,
                'triwulan' => $triwulan,
                'tahun' => $tahun,
                'evaluasi_kinerja' => $this->resolveEvaluasiKinerja($payload),
                'status' => IpalTriwulan::STATUS_BERLANGSUNG,
            ]);

            $this->syncDetails($laporan, $payload['bulan_list'] ?? []);
            $this->syncDampak($laporan, $payload['evaluasi'] ?? []);

            return $laporan->fresh(['detailHarian', 'dampakLingkungan']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(LaporanIpal $laporan, array $payload): LaporanIpal
    {
        $triwulan = IpalTriwulan::numberFromKey((string) $payload['triwulan_key']);
        $tahun = (int) $payload['tahun'];

        $this->assertPeriodeAvailable($triwulan, $tahun, $laporan->id);

        return DB::transaction(function () use ($laporan, $payload, $triwulan, $tahun) {
            $laporan->update([
                'triwulan' => $triwulan,
                'tahun' => $tahun,
                'evaluasi_kinerja' => $this->resolveEvaluasiKinerja($payload),
            ]);

            $laporan->detailHarian()->delete();
            $this->syncDetails($laporan, $payload['bulan_list'] ?? []);
            $this->syncDampak($laporan, $payload['evaluasi'] ?? []);

            return $laporan->fresh(['detailHarian', 'dampakLingkungan']);
        });
    }

    public function markSelesai(LaporanIpal $laporan): LaporanIpal
    {
        $laporan->update([
            'status' => IpalTriwulan::STATUS_SELESAI,
        ]);

        $laporan = $laporan->fresh(['detailHarian', 'dampakLingkungan', 'petugas']);

        $petugas = $laporan->petugas ?? User::query()->find($laporan->petugas_id);

        if ($petugas !== null) {
            $this->laporanGenerateService->generate($petugas, $laporan);
        }

        return $laporan;
    }

    public function destroy(LaporanIpal $laporan): void
    {
        $petugas = User::query()->find($laporan->petugas_id);

        DB::transaction(function () use ($laporan): void {
            $laporan->detailHarian()->delete();
            $laporan->dampakLingkungan()?->delete();
            $laporan->delete();
        });

        if ($petugas !== null) {
            $this->laporanRegistryService->deleteDocx(
                $petugas,
                LaporanGenerated::JENIS_IPAL,
                IpalTriwulan::label($laporan->triwulan, $laporan->tahun),
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $bulanList
     */
    private function syncDetails(LaporanIpal $laporan, array $bulanList): void
    {
        foreach ($bulanList as $bulan) {
            $bulanNumber = IpalTriwulan::bulanNumberFromName((string) ($bulan['nama'] ?? ''));

            if ($bulanNumber === 0) {
                continue;
            }

            foreach ($bulan['catatan'] ?? [] as $catatan) {
                DetailIpalHarian::query()->create([
                    'laporan_ipal_id' => $laporan->id,
                    'bulan' => $bulanNumber,
                    'tanggal_sampling' => $catatan['tanggal'],
                    'debit_input_m3' => $catatan['debit_in'],
                    'debit_output_m3' => $catatan['debit_out'],
                    'ph' => $catatan['ph'],
                    'suhu_celcius' => $catatan['suhu'],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $evaluasi
     */
    private function syncDampak(LaporanIpal $laporan, array $evaluasi): void
    {
        $laporan->dampakLingkungan()?->delete();

        if (! $this->shouldPersistDampak($evaluasi)) {
            return;
        }

        DampakLingkunganIpal::query()->create([
            'laporan_ipal_id' => $laporan->id,
            'jenis_dampak' => trim((string) ($evaluasi['jenis_dampak'] ?? '')),
            'sumber_dampak' => trim((string) ($evaluasi['sumber_dampak'] ?? '')),
            'parameter_pemantauan' => trim((string) ($evaluasi['parameter_pemantauan'] ?? '')),
            'tolak_ukur' => trim((string) ($evaluasi['tolak_ukur'] ?? '')),
            'lokasi_pengelolaan' => $this->nullableString($evaluasi['lokasi_pengelolaan'] ?? null),
            'evaluasi_hasil' => $this->nullableString($evaluasi['evaluasi_hasil'] ?? null),
            'tindakan_perbaikan' => $this->nullableString($evaluasi['tindakan_perbaikan'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveEvaluasiKinerja(array $payload): ?string
    {
        $evaluasi = $payload['evaluasi'] ?? [];
        $text = trim((string) ($evaluasi['evaluasi_hasil'] ?? ''));

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $evaluasi
     */
    private function shouldPersistDampak(array $evaluasi): bool
    {
        foreach (['jenis_dampak', 'sumber_dampak', 'parameter_pemantauan', 'tolak_ukur'] as $field) {
            if (trim((string) ($evaluasi[$field] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function assertPeriodeAvailable(?int $triwulan, int $tahun, ?int $ignoreId = null): void
    {
        if ($triwulan === null) {
            throw ValidationException::withMessages([
                'triwulan_key' => 'Periode triwulan tidak valid.',
            ]);
        }

        $exists = LaporanIpal::query()
            ->where('triwulan', $triwulan)
            ->where('tahun', $tahun)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'triwulan_key' => 'Laporan untuk periode triwulan dan tahun ini sudah ada.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeListItem(LaporanIpal $row): array
    {
        $bulanTerisi = $this->countBulanTerisi($row);
        $catatanCount = $this->countCatatan($row);
        $isSelesai = $row->status === IpalTriwulan::STATUS_SELESAI;
        $progress = $isSelesai
            ? ($bulanTerisi >= 3 ? '3 bulan terisi' : 'Periode ditutup')
            : sprintf('%d dari 3 bulan terisi', min($bulanTerisi, 3));

        $tanggal = $row->updated_at ?? $row->created_at;

        return [
            'id' => $row->id,
            'triwulan' => IpalTriwulan::label($row->triwulan, $row->tahun),
            'triwulanKey' => IpalTriwulan::keyFromNumber($row->triwulan),
            'tahun' => (string) $row->tahun,
            'status' => $row->status,
            'progress' => $progress,
            'tanggal' => $tanggal?->format('d/m/Y') ?? '-',
            'nama_laporan' => IpalTriwulan::label($row->triwulan, $row->tahun),
            'jumlah' => $catatanCount > 0
                ? $catatanCount.' catatan · '.$progress
                : $progress,
        ];
    }

    private function countCatatan(LaporanIpal $row): int
    {
        if ($row->relationLoaded('detailHarian')) {
            return $row->detailHarian->count();
        }

        return (int) DetailIpalHarian::query()
            ->where('laporan_ipal_id', $row->id)
            ->count();
    }

    private function countBulanTerisi(LaporanIpal $row): int
    {
        if ($row->relationLoaded('detailHarian')) {
            return $row->detailHarian->pluck('bulan')->unique()->count();
        }

        return (int) DetailIpalHarian::query()
            ->where('laporan_ipal_id', $row->id)
            ->distinct()
            ->count('bulan');
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
