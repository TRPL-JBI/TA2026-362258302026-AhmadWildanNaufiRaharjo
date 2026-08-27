<?php

namespace App\Http\Controllers\Pemantauan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pemantauan\StoreLaporanIpalRequest;
use App\Http\Requests\Pemantauan\UpdateLaporanIpalRequest;
use App\Models\LaporanIpal;
use App\Services\Pemantauan\PemantauanIpalService;
use App\Support\IpalTriwulan;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PemantauanIpalController extends Controller
{
    public function __construct(
        private readonly PemantauanIpalService $ipalService,
    ) {}

    public function index(): View
    {
        return view('pemantauan.ipal', [
            'ipalPageConfig' => [
                'initialReports' => $this->ipalService->listForIndex(),
                'triwulanToBulan' => IpalTriwulan::triwulanToBulanMap(),
                'tahunOptions' => IpalTriwulan::defaultTahunOptions(),
                'defaultTahun' => (string) date('Y'),
                'storeUrl' => route('pemantauan.ipal.store', [], false),
                'baseUrl' => url('/pemantauan/ipal'),
            ],
        ]);
    }

    public function show(LaporanIpal $laporanIpal): JsonResponse
    {
        return response()->json([
            'data' => $this->ipalService->serializeForEdit($laporanIpal),
        ]);
    }

    public function store(StoreLaporanIpalRequest $request): JsonResponse
    {
        $laporan = $this->ipalService->store(
            $request->user(),
            $this->normalizePayload($request->validated()),
        );

        $listItem = collect($this->ipalService->listForIndex())
            ->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan pemantauan IPAL berhasil disimpan.',
            'data' => $this->ipalService->serializeForEdit($laporan),
            'listItem' => $listItem,
        ]);
    }

    public function update(UpdateLaporanIpalRequest $request, LaporanIpal $laporanIpal): JsonResponse
    {
        $laporan = $this->ipalService->update(
            $laporanIpal,
            $this->normalizePayload($request->validated()),
        );

        $listItem = collect($this->ipalService->listForIndex())
            ->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan pemantauan IPAL berhasil diperbarui.',
            'data' => $this->ipalService->serializeForEdit($laporan),
            'listItem' => $listItem,
        ]);
    }

    public function markSelesai(LaporanIpal $laporanIpal): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $laporan = $this->ipalService->markSelesai($laporanIpal);

        $list = collect($this->ipalService->listForIndex());
        $listItem = $list->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan ditandai selesai.',
            'listItem' => $listItem,
        ]);
    }

    public function destroy(LaporanIpal $laporanIpal): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $this->ipalService->destroy($laporanIpal);

        return response()->json([
            'message' => 'Laporan pemantauan IPAL berhasil dihapus.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePayload(array $validated): array
    {
        $evaluasi = $validated['evaluasi'] ?? [];

        return [
            'triwulan_key' => $validated['triwulan_key'],
            'tahun' => $validated['tahun'],
            'bulan_list' => collect($validated['bulan_list'] ?? [])
                ->map(fn (array $bulan) => [
                    'nama' => $bulan['nama'],
                    'catatan' => collect($bulan['catatan'] ?? [])
                        ->map(fn (array $catatan) => [
                            'tanggal' => $catatan['tanggal'],
                            'debit_in' => $catatan['debit_in'],
                            'debit_out' => $catatan['debit_out'],
                            'ph' => $catatan['ph'],
                            'suhu' => $catatan['suhu'],
                        ])
                        ->all(),
                ])
                ->all(),
            'evaluasi' => [
                'jenis_dampak' => $evaluasi['jenis_dampak'] ?? null,
                'sumber_dampak' => $evaluasi['sumber_dampak'] ?? null,
                'parameter_pemantauan' => $evaluasi['parameter_pemantauan'] ?? null,
                'tolak_ukur' => $evaluasi['tolak_ukur'] ?? null,
                'lokasi_pengelolaan' => $evaluasi['lokasi_pengelolaan'] ?? null,
                'evaluasi_hasil' => $evaluasi['evaluasi_hasil'] ?? null,
                'tindakan_perbaikan' => $evaluasi['tindakan_perbaikan'] ?? null,
            ],
        ];
    }
}
