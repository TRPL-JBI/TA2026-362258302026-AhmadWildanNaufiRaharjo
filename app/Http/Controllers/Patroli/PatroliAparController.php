<?php

namespace App\Http\Controllers\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patroli\StorePemeriksaanAparPatroliRequest;
use App\Models\PatroliLaporanPeriode;
use App\Services\Patroli\PatroliAparDraftBuilder;
use App\Services\Patroli\PatroliAparService;
use App\Services\Patroli\PatroliLaporanPeriodeService;
use App\Services\Patroli\PatroliQrResolver;
use App\Support\PatroliPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatroliAparController extends Controller
{
    public function __construct(
        private readonly PatroliAparService $aparService,
        private readonly PatroliQrResolver $qrResolver,
        private readonly PatroliAparDraftBuilder $aparDraftBuilder,
        private readonly PatroliLaporanPeriodeService $laporanPeriodeService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $initialApar = null;
        $scanError = null;
        $payload = trim((string) $request->query('q', ''));
        $continueLokasiSections = [];
        $showContinueLoading = false;
        $readOnly = false;

        $continuePeriode = $request->query('continue_periode');

        if (is_string($continuePeriode) && PatroliPeriode::isValidKey($continuePeriode)) {
            $readOnly = $this->laporanPeriodeService->isSelesai(
                $request->user(),
                $continuePeriode,
                PatroliLaporanPeriode::JENIS_APAR,
            );

            try {
                $continueLokasiSections = $this->aparDraftBuilder->lokasiSectionsForContinue(
                    $request->user(),
                    $continuePeriode,
                    $readOnly,
                );
                $request->session()->put('patroli_continue_periode', $continuePeriode);
                $showContinueLoading = $continueLokasiSections !== [];
            } catch (ValidationException $exception) {
                $scanError = collect($exception->errors())->flatten()->first()
                    ?? 'Tidak dapat melanjutkan pemeriksaan untuk periode ini.';
            }
        }

        if ($payload !== '') {
            $resolved = $this->qrResolver->resolve($payload);

            if (($resolved['type'] ?? '') === 'lokasi' && isset($resolved['section'])) {
                return redirect()->route('patroli.temuan', ['q' => $payload]);
            }

            if (isset($resolved['apar'])) {
                $initialApar = $resolved['apar'];
            } elseif (isset($resolved['message'])) {
                $scanError = $resolved['message'];
            }
        }

        return view('patroli.apar', [
            'initialApar' => $initialApar,
            'scanError' => $scanError,
            'scanPayload' => $initialApar === null ? $payload : '',
            'temuanHref' => route('patroli.temuan', [], false),
            'continueLokasiSections' => $continueLokasiSections,
            'showContinueLoading' => $showContinueLoading,
            'readOnly' => $readOnly,
            'storeUrl' => route('patroli.apar.store', [], false),
            'resolveUrl' => route('patroli.qr.resolve', [], false),
        ]);
    }

    public function store(StorePemeriksaanAparPatroliRequest $request): JsonResponse
    {
        $syncPeriode = $request->session()->get('patroli_continue_periode');
        $syncPeriode = is_string($syncPeriode) && PatroliPeriode::isValidKey($syncPeriode)
            ? $syncPeriode
            : null;

        $result = $this->aparService->store(
            $request->user(),
            $request->validated('pemeriksaan') ?? [],
            $request->fotoAparFiles(),
            $syncPeriode,
        );

        $request->session()->forget('patroli_continue_periode');

        $savedPeriode = $result['periode'] ?? PatroliPeriode::keyFromDate(now());

        $request->session()->flash(
            'patroli_success',
            'Pemeriksaan APAR tersimpan ('.$result['count'].' unit) untuk '.PatroliPeriode::displayLabel($savedPeriode).'.',
        );

        return response()->json([
            'message' => 'Pemeriksaan APAR berhasil disimpan.',
            'data' => $result,
            'redirect' => route('patroli.riwayat', ['jenis' => 'apar'], false),
            'detail_redirect' => route('patroli.riwayat', ['periode' => $savedPeriode], false),
        ]);
    }
}
