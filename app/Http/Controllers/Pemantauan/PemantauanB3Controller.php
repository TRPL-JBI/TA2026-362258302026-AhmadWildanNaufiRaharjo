<?php

namespace App\Http\Controllers\Pemantauan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pemantauan\StoreLaporanB3Request;
use App\Http\Requests\Pemantauan\UpdateLaporanB3Request;
use App\Models\LaporanLimbahB3;
use App\Services\Pemantauan\PemantauanB3Service;
use App\Support\B3Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PemantauanB3Controller extends Controller
{
    public function __construct(
        private readonly PemantauanB3Service $b3Service,
    ) {}

    public function index(): View
    {
        $user = request()->user();
        $canManage = $user?->hasRole('Petugas K3LH') ?? false;

        return view('pemantauan.b3', [
            'b3PageConfig' => [
                'initialReports' => $this->b3Service->listForIndex(),
                'semesterToBulan' => B3Semester::semesterToBulanMap(),
                'tahunOptions' => array_map(
                    fn (int $year) => (string) $year,
                    B3Semester::defaultTahunOptions(),
                ),
                'defaultTahun' => (string) date('Y'),
                'canManage' => $canManage,
                'storeUrl' => route('pemantauan.b3.store', [], false),
                'baseUrl' => url('/pemantauan/b3'),
            ],
        ]);
    }

    public function show(LaporanLimbahB3 $laporanLimbahB3): JsonResponse
    {
        abort_unless(
            request()->user()?->hasRole('Petugas K3LH') || request()->user()?->hasRole('Kalab'),
            403,
        );

        return response()->json([
            'data' => $this->b3Service->serializeForEdit($laporanLimbahB3),
        ]);
    }

    public function store(StoreLaporanB3Request $request): JsonResponse
    {
        $laporan = $this->b3Service->store(
            $request->user(),
            $this->normalizePayload($request->validated()),
        );

        $listItem = collect($this->b3Service->listForIndex())
            ->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan pemantauan B3 berhasil disimpan.',
            'data' => $this->b3Service->serializeForEdit($laporan),
            'listItem' => $listItem,
        ]);
    }

    public function update(UpdateLaporanB3Request $request, LaporanLimbahB3 $laporanLimbahB3): JsonResponse
    {
        $laporan = $this->b3Service->update(
            $laporanLimbahB3,
            $this->normalizePayload($request->validated()),
        );

        $listItem = collect($this->b3Service->listForIndex())
            ->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan pemantauan B3 berhasil diperbarui.',
            'data' => $this->b3Service->serializeForEdit($laporan),
            'listItem' => $listItem,
        ]);
    }

    public function markSelesai(LaporanLimbahB3 $laporanLimbahB3): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $laporan = $this->b3Service->markSelesai($laporanLimbahB3);

        $listItem = collect($this->b3Service->listForIndex())
            ->firstWhere('id', $laporan->id);

        return response()->json([
            'message' => 'Laporan ditandai selesai.',
            'listItem' => $listItem,
        ]);
    }

    public function destroy(LaporanLimbahB3 $laporanLimbahB3): JsonResponse
    {
        abort_unless(request()->user()?->hasRole('Petugas K3LH'), 403);

        $this->b3Service->destroy($laporanLimbahB3);

        return response()->json([
            'message' => 'Laporan pemantauan B3 berhasil dihapus.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePayload(array $validated): array
    {
        return [
            'semester' => $validated['semester'],
            'tahun' => $validated['tahun'],
            'jenis_list' => collect($validated['jenis_list'] ?? [])
                ->map(fn (array $jenis) => [
                    'nama_limbah' => $jenis['nama_limbah'],
                    'kode_limbah' => $jenis['kode_limbah'],
                    'sumber_limbah' => $jenis['sumber_limbah'],
                    'karakteristik' => $jenis['karakteristik'],
                    'pengemasan' => $jenis['pengemasan'],
                    'masa_simpan_hari' => $jenis['masa_simpan_hari'],
                ])
                ->all(),
            'logbook_bulan_list' => collect($validated['logbook_bulan_list'] ?? [])
                ->map(fn (array $bulan) => [
                    'nama' => $bulan['nama'],
                    'entries' => collect($bulan['entries'] ?? [])
                        ->map(fn (array $entry) => [
                            'tanggal_masuk' => $entry['tanggal_masuk'] ?? null,
                            'tanggal_keluar' => $entry['tanggal_keluar'] ?? null,
                            'jenis_limbah' => $entry['jenis_limbah'] ?? null,
                            'sumber_limbah' => $entry['sumber_limbah'] ?? null,
                            'jumlah_masuk_kg' => $entry['jumlah_masuk_kg'] ?? null,
                            'jumlah_keluar_kg' => $entry['jumlah_keluar_kg'] ?? null,
                            'pengemasan' => $entry['pengemasan'] ?? null,
                        ])
                        ->all(),
                ])
                ->all(),
            'manifest_list' => collect($validated['manifest_list'] ?? [])
                ->map(fn (array $manifest) => $manifest)
                ->all(),
        ];
    }
}
