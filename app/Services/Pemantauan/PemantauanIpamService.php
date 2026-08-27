<?php

namespace App\Services\Pemantauan;

use App\Models\DetailIpamMingguan;
use App\Models\LaporanGenerated;
use App\Models\LaporanIpam;
use App\Models\TitikIpam;
use App\Models\UnitIpam;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\IpamAltFormat;
use App\Support\IpamBulan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemantauanIpamService
{
    public function __construct(
        private readonly LaporanRegistryService $laporanRegistryService,
        private readonly PemantauanIpamLaporanGenerateService $laporanGenerateService,
    ) {}
    /**
     * @return list<array<string, mixed>>
     */
    public function inventarisForPage(): array
    {
        $units = UnitIpam::query()
            ->with('titikIpam')
            ->orderBy('nama_unit')
            ->get();

        $unitIpamData = $units->map(fn (UnitIpam $unit) => [
            'id' => $unit->id,
            'nama_unit' => $unit->nama_unit,
        ])->values()->all();

        $titikIpamData = $units->flatMap(
            fn (UnitIpam $unit) => $unit->titikIpam->map(fn (TitikIpam $titik) => [
                'id' => $titik->id,
                'unit_id' => $titik->unit_ipam_id,
                'nama_titik' => $titik->titik_lokasi,
            ]),
        )->values()->all();

        return [
            'unitIpamData' => $unitIpamData,
            'titikIpamData' => $titikIpamData,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndex(): array
    {
        $laporan = LaporanIpam::query()
            ->with(['titikIpam.unitIpam', 'detailMingguan'])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();

        return $laporan
            ->groupBy(fn (LaporanIpam $row) => IpamBulan::periodeKey($row->tahun, $row->bulan))
            ->map(fn (Collection $rows, string $periodeKey) => $this->serializeListItem($periodeKey, $rows))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeForEdit(int $tahun, int $bulan): array
    {
        $laporan = $this->laporanForPeriode($tahun, $bulan);

        if ($laporan->isEmpty()) {
            abort(404);
        }

        return $this->buildEditPayload($tahun, $bulan, $laporan);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(User $user, array $payload): array
    {
        $bulan = IpamBulan::bulanNumberFromName((string) $payload['bulan']);
        $tahun = (int) $payload['tahun'];

        $this->assertPeriodeAvailable($bulan, $tahun);

        DB::transaction(function () use ($user, $payload, $bulan, $tahun): void {
            $this->persistPeriode($user, $bulan, $tahun, $payload);
        });

        $periodeKey = IpamBulan::periodeKey($tahun, $bulan);
        $rows = $this->laporanForPeriode($tahun, $bulan);

        return [
            'periodeKey' => $periodeKey,
            'edit' => $this->buildEditPayload($tahun, $bulan, $rows),
            'listItem' => $this->serializeListItem($periodeKey, $rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, int $tahun, int $bulan, array $payload): array
    {
        $newBulan = IpamBulan::bulanNumberFromName((string) $payload['bulan']);
        $newTahun = (int) $payload['tahun'];

        if ($newBulan !== $bulan || $newTahun !== $tahun) {
            $this->assertPeriodeAvailable($newBulan, $newTahun);
        }

        DB::transaction(function () use ($user, $payload, $bulan, $tahun, $newBulan, $newTahun): void {
            $this->deletePeriode($bulan, $tahun);
            $this->persistPeriode($user, $newBulan, $newTahun, $payload);
        });

        return $this->afterPersist($newTahun, $newBulan);
    }

    public function destroy(int $tahun, int $bulan): void
    {
        DB::transaction(function () use ($bulan, $tahun): void {
            $this->deletePeriode($bulan, $tahun);
        });
    }

    public function markSelesai(int $tahun, int $bulan): array
    {
        $rows = $this->laporanForPeriode($tahun, $bulan);

        if ($rows->isEmpty()) {
            abort(404);
        }

        LaporanIpam::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->update(['status' => IpamBulan::STATUS_SELESAI]);

        $petugasUser = User::query()->find($rows->first()?->petugas_id);

        if ($petugasUser !== null) {
            $this->laporanGenerateService->generate($petugasUser, $tahun, $bulan);
        }

        $periodeKey = IpamBulan::periodeKey($tahun, $bulan);
        $rows = $this->laporanForPeriode($tahun, $bulan);

        return [
            'listItem' => $this->serializeListItem($periodeKey, $rows),
        ];
    }

    /**
     * @return array{periodeKey: string, edit: array<string, mixed>, listItem: array<string, mixed>}
     */
    private function afterPersist(int $tahun, int $bulan): array
    {
        $periodeKey = IpamBulan::periodeKey($tahun, $bulan);
        $rows = $this->laporanForPeriode($tahun, $bulan);

        return [
            'periodeKey' => $periodeKey,
            'edit' => $this->buildEditPayload($tahun, $bulan, $rows),
            'listItem' => $this->serializeListItem($periodeKey, $rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persistPeriode(User $user, int $bulan, int $tahun, array $payload): void
    {
        $notes = $payload['notes'] ?? [];
        $kesimpulan = $this->nullableString($notes['kesimpulan'] ?? null);
        $kendala = $this->nullableString($notes['kendala'] ?? null);
        $rekomendasi = $this->nullableString($notes['rekomendasi'] ?? null);

        /** @var array<int, LaporanIpam> $laporanByTitik */
        $laporanByTitik = [];
        $savedDetails = 0;

        foreach ($payload['units'] ?? [] as $unitPayload) {
            foreach ($unitPayload['minggu_list'] ?? [] as $mingguPayload) {
                $mingguKe = (int) ($mingguPayload['minggu_ke'] ?? 0);
                if ($mingguKe < 1) {
                    continue;
                }

                foreach ($mingguPayload['data_titik'] ?? [] as $row) {
                    if (! is_array($row) || ! $this->titikRowIsComplete($row)) {
                        continue;
                    }

                    $titikId = (int) ($row['titik_id'] ?? 0);
                    if ($titikId === 0) {
                        continue;
                    }

                    if (! isset($laporanByTitik[$titikId])) {
                        $laporanByTitik[$titikId] = LaporanIpam::query()->create([
                            'titik_ipam_id' => $titikId,
                            'petugas_id' => $user->id,
                            'bulan' => $bulan,
                            'tahun' => $tahun,
                            'kesimpulan' => $kesimpulan,
                            'status' => IpamBulan::STATUS_BERLANGSUNG,
                        ]);
                    }

                    DetailIpamMingguan::query()->create([
                        'laporan_ipam_id' => $laporanByTitik[$titikId]->id,
                        'minggu_ke' => $mingguKe,
                        'ph' => (float) $row['ph'],
                        'alt_cfu_ml' => IpamAltFormat::normalize((string) $row['alt']),
                        'salmonella' => (string) $row['salmonella'],
                        'status' => (string) $row['status'],
                        'kendala' => $mingguKe === 1 ? $kendala : null,
                        'rekomendasi' => $mingguKe === 1 ? $rekomendasi : null,
                    ]);

                    $savedDetails++;
                }
            }
        }

        if ($savedDetails === 0) {
            throw ValidationException::withMessages([
                'units' => 'Minimal isi data lengkap untuk satu titik.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function titikRowIsComplete(array $row): bool
    {
        $ph = $row['ph'] ?? null;
        $alt = trim((string) ($row['alt'] ?? ''));
        $salmonella = (string) ($row['salmonella'] ?? '');
        $status = (string) ($row['status'] ?? '');

        if ($ph === null || $ph === '' || $alt === '' || $salmonella === '' || $status === '') {
            return false;
        }

        return IpamAltFormat::isValid($alt);
    }

    /**
     * @param  Collection<int, LaporanIpam>  $laporan
     * @return array<string, mixed>
     */
    private function buildEditPayload(int $tahun, int $bulan, Collection $laporan): array
    {
        $laporan->loadMissing(['titikIpam.unitIpam', 'detailMingguan']);

        $first = $laporan->first();
        $firstDetail = $laporan
            ->flatMap(fn (LaporanIpam $row) => $row->detailMingguan)
            ->sortBy('minggu_ke')
            ->first();

        $unitsById = [];

        foreach ($laporan->groupBy(fn (LaporanIpam $row) => $row->titikIpam->unit_ipam_id) as $unitId => $rows) {
            $mingguKeys = $rows
                ->flatMap(fn (LaporanIpam $row) => $row->detailMingguan->pluck('minggu_ke'))
                ->unique()
                ->sort()
                ->values();

            $mingguList = [];

            $titikIds = TitikIpam::query()
                ->where('unit_ipam_id', (int) $unitId)
                ->orderBy('titik_lokasi')
                ->pluck('id');

            foreach ($mingguKeys as $mingguKe) {
                $dataTitik = [];

                foreach ($titikIds as $titikId) {
                    $row = $rows->firstWhere('titik_ipam_id', $titikId);
                    $detail = $row?->detailMingguan->firstWhere('minggu_ke', $mingguKe);

                    $dataTitik[$titikId] = [
                        'ph' => $detail !== null ? (string) $detail->ph : '',
                        'alt' => $detail !== null ? (string) $detail->alt_cfu_ml : '',
                        'salmonella' => $detail?->salmonella ?? '',
                        'status' => $detail?->status ?? '',
                    ];
                }

                $mingguList[] = [
                    'mingguKe' => (int) $mingguKe,
                    'dataTitik' => $dataTitik,
                ];
            }

            $unitsById[(int) $unitId] = [
                'unitId' => (int) $unitId,
                'expanded' => true,
                'mingguList' => array_values(array_map(
                    fn (array $minggu, int $index) => array_merge($minggu, ['expanded' => $index === 0]),
                    $mingguList,
                    array_keys($mingguList),
                )),
            ];
        }

        return [
            'id' => IpamBulan::periodeKey($tahun, $bulan),
            'bulan' => IpamBulan::bulanNameFromNumber($bulan),
            'tahun' => (string) $tahun,
            'status' => $first?->status ?? IpamBulan::STATUS_BERLANGSUNG,
            'units' => array_values($unitsById),
            'notes' => [
                'kendala' => $firstDetail?->kendala ?? '',
                'rekomendasi' => $firstDetail?->rekomendasi ?? '',
                'kesimpulan' => $first?->kesimpulan ?? '',
            ],
        ];
    }

    /**
     * @param  Collection<int, LaporanIpam>  $rows
     * @return array<string, mixed>
     */
    private function serializeListItem(string $periodeKey, Collection $rows): array
    {
        $first = $rows->first();
        $bulan = $first?->bulan ?? 0;
        $tahun = $first?->tahun ?? 0;
        $bulanName = IpamBulan::bulanNameFromNumber($bulan);

        $unitCount = $rows
            ->map(fn (LaporanIpam $row) => $row->titikIpam?->unit_ipam_id)
            ->filter()
            ->unique()
            ->count();

        $status = $rows->contains(
            fn (LaporanIpam $row) => $row->status === IpamBulan::STATUS_SELESAI,
        )
            ? IpamBulan::STATUS_SELESAI
            : ($first?->status ?? IpamBulan::STATUS_BERLANGSUNG);

        $isSelesai = $status === IpamBulan::STATUS_SELESAI;
        $progress = $isSelesai
            ? 'Periode ditutup'
            : ($unitCount > 0
                ? sprintf('%d unit', $unitCount)
                : 'Belum ada unit');

        $namaLaporan = sprintf('Pemantauan IPAM - %s %d', $bulanName, $tahun);
        $updatedAt = $rows->max(fn (LaporanIpam $row) => $row->updated_at);

        return [
            'id' => $periodeKey,
            'bulan' => $bulanName,
            'tahun' => (string) $tahun,
            'status' => $status,
            'tanggal' => $updatedAt?->format('d/m/Y') ?? '-',
            'nama_laporan' => $namaLaporan,
            'progress' => $progress,
            'jumlah' => $progress,
        ];
    }

    /**
     * @return Collection<int, LaporanIpam>
     */
    private function laporanForPeriode(int $tahun, int $bulan): Collection
    {
        return LaporanIpam::query()
            ->with(['titikIpam.unitIpam', 'detailMingguan'])
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();
    }

    private function deletePeriode(int $bulan, int $tahun): void
    {
        $rows = LaporanIpam::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        if ($rows->isEmpty()) {
            abort(404);
        }

        $laporanIds = $rows->pluck('id');
        $petugas = User::query()->find($rows->first()?->petugas_id);

        DetailIpamMingguan::query()
            ->whereIn('laporan_ipam_id', $laporanIds)
            ->delete();

        LaporanIpam::query()
            ->whereIn('id', $laporanIds)
            ->delete();

        if ($petugas !== null) {
            $this->laporanRegistryService->deleteDocx(
                $petugas,
                LaporanGenerated::JENIS_IPAM,
                PemantauanIpamLaporanGenerateService::periodeLabel($tahun, $bulan),
            );
        }
    }

    private function assertPeriodeAvailable(int $bulan, int $tahun): void
    {
        if ($bulan < 1 || $bulan > 12) {
            throw ValidationException::withMessages([
                'bulan' => 'Bulan tidak valid.',
            ]);
        }

        $exists = LaporanIpam::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'bulan' => 'Laporan untuk periode bulan dan tahun ini sudah ada.',
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
