<?php

namespace App\Services\Patroli;

use App\Models\LaporanGenerated;
use App\Models\PatroliLaporanPeriode;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Services\Laporan\PatroliReportGenerator;

class PatroliLaporanGenerateService
{
    public function __construct(
        private readonly PatroliRiwayatService $riwayatService,
        private readonly PatroliReportGenerator $reportGenerator,
        private readonly LaporanRegistryService $registryService,
    ) {}

    public function generateTemuan(User $petugas, string $periodeKey): void
    {
        $detail = $this->riwayatService->detailTemuan($petugas, $periodeKey);
        $relativePath = $this->reportGenerator->generateTemuan($petugas, $periodeKey, $detail);

        $this->registryService->registerDocx(
            $petugas,
            PatroliReportGenerator::jenisLaporanForPatroli(PatroliLaporanPeriode::JENIS_TEMUAN),
            PatroliReportGenerator::periodeLabel($periodeKey),
            $relativePath,
        );
    }

    public function generateApar(User $petugas, string $periodeKey): void
    {
        $detail = $this->riwayatService->detailApar($petugas, $periodeKey);
        $relativePath = $this->reportGenerator->generateApar($petugas, $periodeKey, $detail);

        $this->registryService->registerXlsx(
            $petugas,
            PatroliReportGenerator::jenisLaporanForPatroli(PatroliLaporanPeriode::JENIS_APAR),
            PatroliReportGenerator::periodeLabel($periodeKey),
            $relativePath,
        );
    }

    public function regenerateFromRecord(LaporanGenerated $laporan): void
    {
        $user = $laporan->user;

        if ($user === null) {
            return;
        }

        $periodeKey = $this->extractPeriodeKeyFromPath(
            $laporan->file_path_xlsx ?? $laporan->file_path_docx,
        );

        if ($periodeKey === null) {
            return;
        }

        match ($laporan->jenis_laporan) {
            LaporanGenerated::JENIS_K3L => $this->generateTemuan($user, $periodeKey),
            LaporanGenerated::JENIS_INVENTARIS_APAR => $this->generateApar($user, $periodeKey),
            default => null,
        };
    }

    public function extractPeriodeKeyFromPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/patroli/(?:k3l|apar)/\d+/(\d{4}-[1-3])\.(docx|xlsx)$#', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
