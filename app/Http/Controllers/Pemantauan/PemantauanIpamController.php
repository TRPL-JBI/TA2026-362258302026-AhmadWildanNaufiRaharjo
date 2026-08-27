<?php

namespace App\Http\Controllers\Pemantauan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pemantauan\StoreLaporanIpamRequest;
use App\Http\Requests\Pemantauan\UpdateLaporanIpamRequest;
use App\Services\Pemantauan\PemantauanIpamService;
use App\Support\IpamBulan;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PemantauanIpamController extends Controller
{
    public function __construct(
        private readonly PemantauanIpamService $ipamService,
    ) {}

    public function index(): View
    {
        $inventaris = $this->ipamService->inventarisForPage();

        return view('pemantauan.ipam', [
            'ipamPageConfig' => [
                'initialReports' => $this->ipamService->listForIndex(),
                'unitIpamData' => $inventaris['unitIpamData'],
                'titikIpamData' => $inventaris['titikIpamData'],
                'bulanOptions' => IpamBulan::bulanOptions(),
                'tahunOptions' => array_map(
                    strval(...),
                    IpamBulan::defaultTahunOptions(),
                ),
                'defaultBulan' => IpamBulan::bulanNameFromNumber((int) date('n')),
                'defaultTahun' => (string) date('Y'),
                'storeUrl' => route('pemantauan.ipam.store', [], false),
                'baseUrl' => url('/pemantauan/ipam'),
            ],
        ]);
    }

    public function show(string $periodeKey): JsonResponse
    {
        $periode = $this->resolvePeriode($periodeKey);

        return response()->json([
            'data' => $this->ipamService->serializeForEdit($periode['tahun'], $periode['bulan']),
        ]);
    }

    public function store(StoreLaporanIpamRequest $request): JsonResponse
    {
        $result = $this->ipamService->store(
            $request->user(),
            $this->normalizePayload($request->validated()),
        );

        return response()->json([
            'message' => 'Laporan pemantauan IPAM berhasil disimpan.',
            'data' => $result['edit'],
            'listItem' => $result['listItem'],
        ]);
    }

    public function update(UpdateLaporanIpamRequest $request, string $periodeKey): JsonResponse
    {
        $periode = $this->resolvePeriode($periodeKey);

        $result = $this->ipamService->update(
            $request->user(),
            $periode['tahun'],
            $periode['bulan'],
            $this->normalizePayload($request->validated()),
        );

        return response()->json([
            'message' => 'Laporan pemantauan IPAM berhasil diperbarui.',
            'data' => $result['edit'],
            'listItem' => $result['listItem'],
        ]);
    }

    public function markSelesai(string $periodeKey): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $periode = $this->resolvePeriode($periodeKey);
        $result = $this->ipamService->markSelesai($periode['tahun'], $periode['bulan']);

        return response()->json([
            'message' => 'Laporan ditandai selesai.',
            'listItem' => $result['listItem'],
        ]);
    }

    public function destroy(string $periodeKey): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $periode = $this->resolvePeriode($periodeKey);
        $this->ipamService->destroy($periode['tahun'], $periode['bulan']);

        return response()->json([
            'message' => 'Laporan pemantauan IPAM berhasil dihapus.',
        ]);
    }

    /**
     * @return array{tahun: int, bulan: int}
     */
    private function resolvePeriode(string $periodeKey): array
    {
        $periode = IpamBulan::parsePeriodeKey($periodeKey);

        if ($periode === null) {
            abort(404);
        }

        return $periode;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePayload(array $validated): array
    {
        $units = collect($validated['units'] ?? [])
            ->map(function (array $unit) {
                $mingguList = collect($unit['minggu_list'] ?? [])
                    ->map(function (array $minggu) {
                        $dataTitik = [];

                        foreach ($minggu['data_titik'] ?? [] as $row) {
                            if (! is_array($row)) {
                                continue;
                            }

                            $titikId = (int) ($row['titik_id'] ?? 0);
                            if ($titikId === 0) {
                                continue;
                            }

                            $dataTitik[] = [
                                'titik_id' => $titikId,
                                'ph' => $row['ph'],
                                'alt' => $row['alt'],
                                'salmonella' => $row['salmonella'],
                                'status' => $row['status'],
                            ];
                        }

                        return [
                            'minggu_ke' => (int) ($minggu['minggu_ke'] ?? 0),
                            'data_titik' => $dataTitik,
                        ];
                    })
                    ->filter(fn (array $minggu) => $minggu['minggu_ke'] > 0)
                    ->values()
                    ->all();

                return [
                    'unit_id' => (int) ($unit['unit_id'] ?? 0),
                    'minggu_list' => $mingguList,
                ];
            })
            ->filter(fn (array $unit) => $unit['unit_id'] > 0)
            ->values()
            ->all();

        $notes = $validated['notes'] ?? [];

        return [
            'bulan' => $validated['bulan'],
            'tahun' => $validated['tahun'],
            'units' => $units,
            'notes' => [
                'kendala' => $notes['kendala'] ?? null,
                'rekomendasi' => $notes['rekomendasi'] ?? null,
                'kesimpulan' => $notes['kesimpulan'] ?? null,
            ],
        ];
    }
}
