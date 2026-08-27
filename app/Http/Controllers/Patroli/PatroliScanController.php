<?php

namespace App\Http\Controllers\Patroli;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patroli\ResolveQrPatroliRequest;
use App\Models\Apar;
use App\Models\Lokasi;
use App\Services\Patroli\PatroliChecklistResolver;
use App\Services\Patroli\PatroliQrResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PatroliScanController extends Controller
{
    public function __construct(
        private readonly PatroliQrResolver $qrResolver,
        private readonly PatroliChecklistResolver $checklistResolver,
    ) {}

    public function index(Request $request): View
    {
        $scanType = in_array($request->query('type'), ['temuan', 'apar'], true)
            ? $request->query('type')
            : 'umum';

        $continuePatrol = $request->boolean('continue');

        return view('patroli.scan', [
            'scanType' => $scanType,
            'continuePatrol' => $continuePatrol,
            'manualItems' => $this->manualItems($scanType),
            'scanOpts' => [
                'temuanHref' => route('patroli.temuan', [], false),
                'aparHref' => route('patroli.apar', [], false),
                'resolveUrl' => route('patroli.qr.resolve', [], false),
                'scanType' => $scanType,
                'continuePatrol' => $continuePatrol,
            ],
        ]);
    }

    public function resolve(ResolveQrPatroliRequest $request): JsonResponse
    {
        $started = hrtime(true);
        $payload = $request->validated('q');
        $result = $this->qrResolver->resolve($payload);
        $resolveMs = (int) round((hrtime(true) - $started) / 1e6);
        $failed = isset($result['message']) && ! isset($result['section']) && ! isset($result['apar']);

        Log::info('QR-SCAN', [
            'user_id' => $request->user()?->id,
            'type' => $result['type'] ?? 'unknown',
            'status' => $failed ? 'gagal' : 'berhasil',
            'scan_ms' => $request->filled('scan_ms') ? $request->integer('scan_ms') : null,
            'resolve_ms' => $resolveMs,
            'message' => $failed ? ($result['message'] ?? null) : null,
        ]);

        return response()->json($result, $failed ? 422 : 200);
    }

    /**
     * @return list<array{label: string, subLabel: string, kind: string, payload: array<string, mixed>}>
     */
    private function manualItems(string $scanType): array
    {
        if ($scanType === 'apar') {
            return $this->manualAparItems();
        }

        if ($scanType === 'temuan') {
            return $this->manualLokasiItems();
        }

        return array_merge($this->manualLokasiItems(), $this->manualAparItems());
    }

    /**
     * @return list<array{label: string, subLabel: string, kind: string, payload: array<string, mixed>}>
     */
    private function manualLokasiItems(): array
    {
        return Lokasi::query()
            ->orderBy('nama_lokasi')
            ->limit(12)
            ->get()
            ->map(function (Lokasi $lokasi) {
                $checklist = $this->checklistResolver->activeChecklistFor($lokasi);
                $itemCount = $checklist?->items->count() ?? 0;

                return [
                    'kind' => 'lokasi',
                    'label' => $lokasi->nama_lokasi,
                    'subLabel' => ($checklist?->nama_checklist ?? 'Belum ada checklist').' · '.$itemCount.' item',
                    'payload' => [
                        'type' => 'lokasi',
                        'id' => $lokasi->id,
                        'nama' => $lokasi->nama_lokasi,
                        'checklist' => $checklist?->nama_checklist,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, subLabel: string, kind: string, payload: array<string, mixed>}>
     */
    private function manualAparItems(): array
    {
        return Apar::query()
            ->with('lokasi:id,nama_lokasi')
            ->orderBy('kode_apar')
            ->limit(12)
            ->get()
            ->map(fn (Apar $apar) => [
                'kind' => 'apar',
                'label' => $apar->kode_apar,
                'subLabel' => ($apar->lokasi?->nama_lokasi ?? '-').' · '.$apar->jenisKapasitasLabel(),
                'payload' => $this->qrResolver->aparPayload($apar),
            ])
            ->all();
    }
}
