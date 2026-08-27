<?php

namespace App\Services\Pemantauan;

use App\Models\LaporanGenerated;
use App\Models\LaporanLimbahB3;
use App\Models\User;
use App\Services\Laporan\B3SwapantauWordBuilder;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\B3Semester;

class PemantauanB3LaporanGenerateService
{
    public function __construct(
        private readonly B3SwapantauReportDataService $reportDataService,
        private readonly B3SwapantauWordBuilder $wordBuilder,
        private readonly LaporanRegistryService $registryService,
    ) {}

    public function generate(User $petugas, LaporanLimbahB3 $laporan): void
    {
        $detail = $this->reportDataService->detailForReport($laporan);
        $relativePath = $this->buildRelativePath(
            $petugas->id,
            B3Semester::periodeKey($laporan->tahun, $laporan->semester),
        );

        $this->wordBuilder->build($detail, $relativePath);

        $this->registryService->registerDocx(
            $petugas,
            LaporanGenerated::JENIS_B3,
            B3Semester::labelWithYear($laporan->semester, $laporan->tahun),
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

        $laporanB3 = LaporanLimbahB3::query()
            ->where('tahun', $periode['tahun'])
            ->where('semester', $periode['semester'])
            ->first();

        if ($laporanB3 === null) {
            return;
        }

        $this->generate($user, $laporanB3);
    }

    /**
     * @return array{tahun: int, semester: int}|null
     */
    public function extractPeriodeFromPath(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/pemantauan/b3/\d+/(\d{4}-s[12])\.docx$#', $path, $matches) !== 1) {
            return null;
        }

        return B3Semester::parsePeriodeKey($matches[1]);
    }

    private function buildRelativePath(int $userId, string $periodeKey): string
    {
        return sprintf('laporan/pemantauan/b3/%d/%s.docx', $userId, $periodeKey);
    }
}
