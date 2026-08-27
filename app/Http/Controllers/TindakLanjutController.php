<?php

namespace App\Http\Controllers;

use App\Http\Requests\TindakLanjut\UpdateTindakLanjutInsidenRequest;
use App\Http\Requests\TindakLanjut\UpdateTindakLanjutInspeksiRequest;
use App\Models\DetailInspeksi;
use App\Models\LaporanInsiden;
use App\Models\TindakLanjutLaporanPeriode;
use App\Services\TindakLanjut\TindakLanjutPeriodeService;
use App\Services\TindakLanjut\TindakLanjutService;
use App\Support\PatroliPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TindakLanjutController extends Controller
{
    public function __construct(
        private readonly TindakLanjutService $tindakLanjut,
        private readonly TindakLanjutPeriodeService $periodeService,
    ) {}

    public function index(Request $request, ?string $periode = null): View
    {
        $periodeKey = $this->resolvePeriode($request, $periode);
        $periodeState = $this->tindakLanjut->periodeState($periodeKey);

        return view('tindak-lanjut', [
            'items' => $this->tindakLanjut->listItemsForPeriode($periodeKey),
            'periode' => $periodeKey,
            'periodeLabel' => PatroliPeriode::displayLabel($periodeKey),
            'periodeRentang' => PatroliPeriode::rentangTanggal($periodeKey),
            'periodeOptions' => $this->periodeService->periodeOptions(),
            'periodeState' => $periodeState,
            'finishPeriodeUrl' => route('tindak-lanjut.periode.selesai', ['periode' => $periodeKey], false),
        ]);
    }

    public function markPeriodeSelesai(Request $request, string $periode): JsonResponse
    {
        $result = $this->tindakLanjut->markPeriodeSelesai($request->user(), $periode);

        return response()->json([
            'message' => 'Periode tindak lanjut ditandai selesai dan laporan telah dibuat.',
            'status' => TindakLanjutLaporanPeriode::STATUS_SELESAI,
            ...$result,
        ]);
    }

    public function updateInspeksi(UpdateTindakLanjutInspeksiRequest $request, DetailInspeksi $detailInspeksi): JsonResponse
    {
        $result = $this->tindakLanjut->updateInspeksi(
            $request->user(),
            $detailInspeksi,
            $request->validated(),
            $request->file('foto'),
        );

        return response()->json($result);
    }

    public function updateInsiden(UpdateTindakLanjutInsidenRequest $request, LaporanInsiden $laporanInsiden): JsonResponse
    {
        $result = $this->tindakLanjut->updateInsiden(
            $request->user(),
            $laporanInsiden,
            $request->validated(),
            $request->file('foto'),
        );

        return response()->json($result);
    }

    private function resolvePeriode(Request $request, ?string $periode): string
    {
        $candidate = $periode ?? $request->query('periode');

        if (is_string($candidate) && PatroliPeriode::isValidKey($candidate)) {
            return $candidate;
        }

        return PatroliPeriode::keyFromDate(now());
    }
}
