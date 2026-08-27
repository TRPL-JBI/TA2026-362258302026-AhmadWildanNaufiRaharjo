<?php

namespace App\Http\Controllers\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreItemChecklistRequest;
use App\Http\Requests\Inventaris\StoreMasterChecklistRequest;
use App\Models\ItemChecklist;
use App\Models\MasterChecklist;
use App\Models\PatroliLaporanPeriode;
use App\Services\Patroli\PatroliLaporanGenerateService;
use App\Services\Patroli\PatroliRiwayatOverviewService;
use App\Services\Patroli\PatroliRiwayatService;
use App\Support\PatroliPeriode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatroliRiwayatController extends Controller
{
    public function __construct(
        private readonly PatroliRiwayatService $riwayatService,
        private readonly PatroliRiwayatOverviewService $overviewService,
        private readonly PatroliLaporanGenerateService $laporanGenerateService,
    ) {}

    public function index(Request $request, ?string $periode = null): View|RedirectResponse
    {
        if ($periode === null && $request->query('periode') === null && ! $request->session()->has('patroli_continue_periode')) {
            $path = $request->path();

            if ($path === 'patroli/riwayat/temuan' || $path === 'patroli/riwayat/apar') {
                return redirect()->route('patroli.riwayat');
            }
        }

        $periodeKey = $this->resolvePeriode($request, $periode);

        if (
            (is_string($periode) && PatroliPeriode::isValidKey($periode))
            || (is_string($request->query('periode')) && PatroliPeriode::isValidKey((string) $request->query('periode')))
        ) {
            $request->session()->forget('patroli_continue_periode');
        }

        return view('patroli.riwayat', [
            'overview' => $this->overviewService->overview($request->user(), $periodeKey),
            'periodeOptions' => $this->overviewService->periodeOptions($request->user()),
            'storeChecklistUrl' => route('patroli.riwayat.temuan.checklist.store', ['periode' => $periodeKey], false),
            'storeItemUrlTemplate' => route('patroli.riwayat.temuan.items.store', [
                'periode' => $periodeKey,
                'masterChecklist' => '__ID__',
            ], false),
            'toggleItemUrlTemplate' => route('patroli.riwayat.temuan.items.toggle-status', [
                'periode' => $periodeKey,
                'itemChecklist' => '__ID__',
            ], false),
        ]);
    }

    public function showApar(Request $request, string $periode): RedirectResponse
    {
        return redirect()->route('patroli.riwayat', ['periode' => $periode]);
    }

    public function continueTemuan(Request $request, string $periode): RedirectResponse
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            return redirect()->route('patroli.riwayat')->with('error', 'Periode patroli tidak valid.');
        }

        $request->session()->put('patroli_continue_periode', $periode);

        return redirect()->route('patroli.riwayat', ['periode' => $periode]);
    }

    public function continueApar(Request $request, string $periode): RedirectResponse
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            return redirect()->route('patroli.riwayat')->with('error', 'Periode patroli tidak valid.');
        }

        $request->session()->put('patroli_continue_periode', $periode);

        return redirect()->route('patroli.riwayat', ['periode' => $periode]);
    }

    public function storeChecklist(StoreMasterChecklistRequest $request, string $periode): JsonResponse
    {
        $checklist = $this->overviewService->storeChecklist(
            $request->user(),
            $periode,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Checklist berhasil dibuat.',
            'data' => [
                'id' => $checklist->id,
                'nama_checklist' => $checklist->nama_checklist,
                'lokasi_id' => $checklist->lokasi_id,
            ],
            'redirect' => route('patroli.riwayat', ['periode' => $periode], false),
        ]);
    }

    public function storeItem(
        StoreItemChecklistRequest $request,
        string $periode,
        MasterChecklist $masterChecklist,
    ): JsonResponse {
        $item = $this->overviewService->storeItem(
            $request->user(),
            $periode,
            $masterChecklist,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Item temuan bahaya berhasil ditambahkan.',
            'data' => [
                'id' => $item->id,
                'nama_item' => $item->nama_item,
            ],
            'redirect' => route('patroli.riwayat', ['periode' => $periode], false),
        ]);
    }

    public function toggleItemStatus(
        Request $request,
        string $periode,
        ItemChecklist $itemChecklist,
    ): JsonResponse {
        $item = $this->overviewService->toggleItemStatus(
            $request->user(),
            $periode,
            $itemChecklist,
        );

        return response()->json([
            'message' => 'Status item diperbarui.',
            'data' => [
                'id' => $item->id,
                'status' => $item->status,
                'aktif' => $item->status === 'Aktif',
            ],
        ]);
    }

    public function destroyTemuan(Request $request, string $periode): JsonResponse|RedirectResponse
    {
        $count = $this->riwayatService->destroyTemuanByPeriode($request->user(), $periode);

        return $this->destroyResponse($request, $count, 'Inspeksi temuan', $periode);
    }

    public function destroyApar(Request $request, string $periode): JsonResponse|RedirectResponse
    {
        $count = $this->riwayatService->destroyAparByPeriode($request->user(), $periode);

        return $this->destroyResponse($request, $count, 'Pemeriksaan APAR', $periode);
    }

    public function markTemuanSelesai(Request $request, string $periode): JsonResponse
    {
        $this->riwayatService->markTemuanSelesai($request->user(), $periode);
        $this->laporanGenerateService->generateTemuan($request->user(), $periode);

        return response()->json([
            'message' => 'Laporan patroli temuan ditandai selesai.',
            'status' => PatroliLaporanPeriode::STATUS_SELESAI,
            'redirect' => route('patroli.riwayat', ['periode' => $periode], false),
        ]);
    }

    public function markAparSelesai(Request $request, string $periode): JsonResponse
    {
        $this->riwayatService->markAparSelesai($request->user(), $periode);
        $this->laporanGenerateService->generateApar($request->user(), $periode);

        return response()->json([
            'message' => 'Laporan pemeriksaan APAR ditandai selesai.',
            'status' => PatroliLaporanPeriode::STATUS_SELESAI,
            'redirect' => route('patroli.riwayat', ['periode' => $periode], false),
        ]);
    }

    private function resolvePeriode(Request $request, ?string $periode): string
    {
        $candidate = $periode ?? $request->query('periode');

        if (is_string($candidate) && PatroliPeriode::isValidKey($candidate)) {
            return $candidate;
        }

        $sessionPeriode = $request->session()->get('patroli_continue_periode');

        if (is_string($sessionPeriode) && PatroliPeriode::isValidKey($sessionPeriode)) {
            return $sessionPeriode;
        }

        return PatroliPeriode::keyFromDate(now());
    }

    private function destroyResponse(
        Request $request,
        int $count,
        string $label,
        ?string $periode = null,
    ): JsonResponse|RedirectResponse {
        $message = "{$label} berhasil dihapus ({$count} data).";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'deleted' => $count]);
        }

        return redirect()
            ->route('patroli.riwayat', $periode !== null ? ['periode' => $periode] : [])
            ->with('patroli_success', $message);
    }
}
