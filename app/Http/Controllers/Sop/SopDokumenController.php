<?php

namespace App\Http\Controllers\Sop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sop\StoreSopDokumenRequest;
use App\Http\Requests\Sop\UpdateSopDokumenRequest;
use App\Models\SopDokumen;
use App\Services\Sop\SopDokumenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SopDokumenController extends Controller
{
    public function __construct(
        private readonly SopDokumenService $sopDokumenService,
    ) {}

    public function index(Request $request): View
    {
        return view('sop.index', [
            'canManage' => $request->user()?->role === 'Petugas K3LH',
            'sopPageConfig' => [
                'items' => $this->sopDokumenService->listForIndex(),
                'canManage' => $request->user()?->role === 'Petugas K3LH',
                'storeUrl' => route('sop.store', [], false),
                'baseUrl' => url('/sop'),
            ],
        ]);
    }

    public function preview(SopDokumen $sopDokumen): StreamedResponse
    {
        if (! Storage::disk('local')->exists($sopDokumen->file_path)) {
            abort(404, 'File SOP tidak ditemukan.');
        }

        return Storage::disk('local')->response(
            $sopDokumen->file_path,
            $sopDokumen->original_filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$sopDokumen->original_filename.'"',
            ],
        );
    }

    public function store(StoreSopDokumenRequest $request): JsonResponse
    {
        $dokumen = $this->sopDokumenService->store(
            $request->user(),
            $request->validated(),
            $request->pdfFile(),
        );

        return response()->json([
            'message' => 'Dokumen SOP berhasil diunggah.',
            'item' => $this->sopDokumenService->serialize($dokumen),
        ]);
    }

    public function update(UpdateSopDokumenRequest $request, SopDokumen $sopDokumen): JsonResponse
    {
        $dokumen = $this->sopDokumenService->update(
            $sopDokumen,
            $request->user(),
            $request->validated(),
            $request->pdfFile(),
        );

        return response()->json([
            'message' => 'Dokumen SOP berhasil diperbarui.',
            'item' => $this->sopDokumenService->serialize($dokumen),
        ]);
    }

    public function destroy(SopDokumen $sopDokumen): JsonResponse
    {
        if (request()->user()?->role !== 'Petugas K3LH') {
            abort(403);
        }

        $this->sopDokumenService->destroy($sopDokumen);

        return response()->json([
            'message' => 'Dokumen SOP berhasil dihapus.',
        ]);
    }
}
