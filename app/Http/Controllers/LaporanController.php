<?php

namespace App\Http\Controllers;

use App\Models\LaporanGenerated;
use App\Services\Laporan\LaporanService;
use App\Services\LaporanInsiden\LaporanInsidenLaporanGenerateService;
use App\Services\Patroli\PatroliLaporanGenerateService;
use App\Services\Pemantauan\PemantauanB3LaporanGenerateService;
use App\Services\Pemantauan\PemantauanIpalLaporanGenerateService;
use App\Services\Pemantauan\PemantauanIpamLaporanGenerateService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporanService,
        private readonly PatroliLaporanGenerateService $patroliLaporanGenerateService,
        private readonly PemantauanIpamLaporanGenerateService $ipamLaporanGenerateService,
        private readonly PemantauanB3LaporanGenerateService $b3LaporanGenerateService,
        private readonly PemantauanIpalLaporanGenerateService $ipalLaporanGenerateService,
        private readonly LaporanInsidenLaporanGenerateService $laporanInsidenLaporanGenerateService,
    ) {}

    public function index(): View
    {
        return view('laporan', [
            'generatedReports' => $this->laporanService->listRows(),
        ]);
    }

    public function preview(LaporanGenerated $laporanGenerated): StreamedResponse|Response
    {
        $laporanGenerated = $this->regenerateLaporan($laporanGenerated);
        $path = $this->resolveFilePath($laporanGenerated);
        $filename = basename($path);
        $mime = str_ends_with($path, '.xlsx')
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        return Storage::disk('local')->response($path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(LaporanGenerated $laporanGenerated): StreamedResponse|Response
    {
        $laporanGenerated = $this->regenerateLaporan($laporanGenerated);
        $path = $this->resolveFilePath($laporanGenerated);

        return Storage::disk('local')->download($path, basename($path));
    }

    private function regenerateLaporan(LaporanGenerated $laporanGenerated): LaporanGenerated
    {
        $laporanGenerated->loadMissing('user');

        if (in_array($laporanGenerated->jenis_laporan, [
            LaporanGenerated::JENIS_K3L,
            LaporanGenerated::JENIS_INVENTARIS_APAR,
        ], true)) {
            $this->patroliLaporanGenerateService->regenerateFromRecord($laporanGenerated);
        }

        if ($laporanGenerated->jenis_laporan === LaporanGenerated::JENIS_IPAM) {
            $this->ipamLaporanGenerateService->regenerateFromRecord($laporanGenerated);
        }

        if ($laporanGenerated->jenis_laporan === LaporanGenerated::JENIS_B3) {
            $this->b3LaporanGenerateService->regenerateFromRecord($laporanGenerated);
        }

        if ($laporanGenerated->jenis_laporan === LaporanGenerated::JENIS_IPAL) {
            $this->ipalLaporanGenerateService->regenerateFromRecord($laporanGenerated);
        }

        if ($laporanGenerated->jenis_laporan === LaporanGenerated::JENIS_INSIDEN) {
            $this->laporanInsidenLaporanGenerateService->regenerateFromRecord($laporanGenerated);
        }

        return $laporanGenerated->refresh();
    }

    private function resolveFilePath(LaporanGenerated $laporanGenerated): string
    {
        $path = $laporanGenerated->file_path_docx ?? $laporanGenerated->file_path_xlsx;

        if ($path === null || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File laporan tidak ditemukan.');
        }

        return $path;
    }
}
