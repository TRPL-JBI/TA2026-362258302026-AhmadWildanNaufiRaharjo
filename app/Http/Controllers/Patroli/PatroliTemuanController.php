<?php

namespace App\Http\Controllers\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patroli\StoreInspeksiPatroliRequest;
use App\Models\PatroliLaporanPeriode;
use App\Services\Patroli\PatroliInspeksiService;
use App\Services\Patroli\PatroliLaporanPeriodeService;
use App\Services\Patroli\PatroliQrResolver;
use App\Services\Patroli\PatroliRiwayatService;
use App\Support\PatroliPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatroliTemuanController extends Controller
{
    public function __construct(
        private readonly PatroliInspeksiService $inspeksiService,
        private readonly PatroliQrResolver $qrResolver,
        private readonly PatroliRiwayatService $riwayatService,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $initialSection = null;
        $scanError = null;
        $payload = trim((string) $request->query('q', ''));
        $continueSections = [];
        $showContinueLoading = false;
        $readOnly = false;

        $continuePeriode = $request->query('continue_periode') ?? $request->query('periode');

        if (is_string($continuePeriode) && PatroliPeriode::isValidKey($continuePeriode)) {
            $request->session()->put('patroli_continue_periode', $continuePeriode);
            $readOnly = $this->laporanPeriodeService->isSelesai(
                $request->user(),
                $continuePeriode,
                PatroliLaporanPeriode::JENIS_TEMUAN,
            );

            try {
                $continueSections = $this->riwayatService->sectionsForContinue(
                    $request->user(),
                    $continuePeriode,
                    $readOnly,
                );
                $showContinueLoading = $continueSections !== [];
            } catch (ValidationException $exception) {
                $scanError = collect($exception->errors())->flatten()->first()
                    ?? 'Tidak dapat melanjutkan inspeksi untuk periode ini.';
            }
        }

        if ($payload !== '') {
            $resolved = $this->qrResolver->resolve($payload);

            if (($resolved['type'] ?? '') === 'apar' && isset($resolved['apar'])) {
                return redirect()->route('patroli.apar', ['q' => $payload]);
            }

            if (isset($resolved['section'])) {
                $initialSection = $resolved['section'];
            } elseif (isset($resolved['message'])) {
                $scanError = $resolved['message'];
            }
        }

        return view('patroli.temuan', [
            'initialSection' => $initialSection,
            'scanError' => $scanError,
            'scanPayload' => $initialSection === null ? $payload : '',
            'aparHref' => route('patroli.apar', [], false),
            'continueSections' => $continueSections,
            'showContinueLoading' => $showContinueLoading,
            'readOnly' => $readOnly,
            'storeUrl' => route('patroli.inspeksi.store', [], false),
            'resolveUrl' => route('patroli.qr.resolve', [], false),
        ]);
    }

    public function store(StoreInspeksiPatroliRequest $request): JsonResponse
    {
        $result = $this->inspeksiService->store(
            $request->user(),
            $request->validated('sections'),
            $request->fotoItemFiles(),
        );

        $periodeKey = PatroliPeriode::keyFromDate(now());
        $sessionPeriode = $request->session()->get('patroli_continue_periode');

        if (is_string($sessionPeriode) && PatroliPeriode::isValidKey($sessionPeriode)) {
            $periodeKey = $sessionPeriode;
        }

        $request->session()->forget('patroli_continue_periode');

        return response()->json([
            'message' => 'Inspeksi patroli berhasil disimpan.',
            'data' => $result,
            'redirect' => route('patroli.riwayat', ['periode' => $periodeKey], false),
        ]);
    }
}
