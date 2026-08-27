<?php

namespace App\Http\Controllers\LaporanInsiden;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanInsiden\StoreLaporanInsidenRequest;
use App\Services\LaporanInsiden\LaporanInsidenService;
use App\Support\LaporanInsidenJenis;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LaporanInsidenController extends Controller
{
    public function __construct(
        private readonly LaporanInsidenService $laporanInsidenService,
    ) {}

    public function index(): View
    {
        return view('laporan-insiden', [
            'laporanInsidenPageConfig' => [
                'jenisOptions' => LaporanInsidenJenis::all(),
                'lokasiOptions' => $this->laporanInsidenService->lokasiOptionsForForm(),
                'storeUrl' => route('laporan-insiden.store', [], false),
            ],
        ]);
    }

    public function store(StoreLaporanInsidenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $lokasiId = $validated['lokasi_id'] ?? null;

        $result = $this->laporanInsidenService->store(
            $request->user(),
            [
                'jenis_insiden' => $validated['jenis_insiden'],
                'lokasi_id' => $lokasiId !== null ? (int) $lokasiId : null,
                'lokasi_manual' => $validated['lokasi_manual'] ?? null,
                'tanggal' => $validated['tanggal'],
                'waktu' => $validated['waktu'],
                'kronologi' => $validated['kronologi'],
                'korban_list' => $request->korbanList(),
            ],
            $request->fotoFiles(),
        );

        return response()->json([
            'message' => 'Laporan insiden berhasil dikirim. Petugas K3LH telah diberi notifikasi.',
            'data' => $result,
        ]);
    }
}
