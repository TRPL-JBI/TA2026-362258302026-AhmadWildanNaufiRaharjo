<?php

namespace App\Services\Pemantauan;

use App\Models\LaporanGenerated;
use App\Models\LaporanIpal;
use App\Models\User;
use App\Services\Laporan\IpalSwapantauWordBuilder;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\IpalTriwulan;

class PemantauanIpalLaporanGenerateService
{
    public function __construct(
        private readonly IpalSwapantauReportDataService $reportDataService,
        private readonly IpalSwapantauWordBuilder $wordBuilder,
        private readonly LaporanRegistryService $registryService,
    ) {}

    public function generate(User $petugas, LaporanIpal $laporan): void
    {
        $detail = $this->reportDataService->detailForReport($laporan);
        $relativePath = $this->buildRelativePath(
            $petugas->id,
            IpalTriwulan::periodeKey($laporan->tahun, $laporan->triwulan),
        );

        $this->wordBuilder->build($detail, $relativePath);

        $this->registryService->registerDocx(
            $petugas,
            LaporanGenerated::JENIS_IPAL,
            IpalTriwulan::label($laporan->triwulan, $laporan->tahun),
            $relativePath,
        );
    }

    public function regenerateFromRecord(LaporanGenerated $laporan): void
    {
        $user = $laporan->user;

        if ($user === null) {
            return;
        }

        $periode = $this->extractPeriodeFromPath($laporan->file_path_docx);

        if ($periode === null) {
            return;
        }

        $laporanIpal = LaporanIpal::query()
            ->where('tahun', $periode['tahun'])
            ->where('triwulan', $periode['triwulan'])
            ->first();

        if ($laporanIpal === null) {
            return;
        }

        $this->generate($user, $laporanIpal);
    }

    /**
     * @return array{tahun: int, triwulan: int}|null
     */
    public function extractPeriodeFromPath(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/pemantauan/ipal/\d+/(\d{4}-q[1-4])\.docx$#', $path, $matches) !== 1) {
            return null;
        }

        return IpalTriwulan::parsePeriodeKey($matches[1]);
    }

    private function buildRelativePath(int $userId, string $periodeKey): string
    {
        return sprintf('laporan/pemantauan/ipal/%d/%s.docx', $userId, $periodeKey);
    }
}
