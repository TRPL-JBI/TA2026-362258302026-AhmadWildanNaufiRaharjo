<?php

namespace App\Services\Pemantauan;

use App\Models\LaporanGenerated;
use App\Models\User;
use App\Services\Laporan\IpamRekapExcelBuilder;
use App\Services\Laporan\LaporanRegistryService;
use App\Support\IpamBulan;

class PemantauanIpamLaporanGenerateService
{
    public function __construct(
        private readonly IpamRekapReportDataService $reportDataService,
        private readonly IpamRekapExcelBuilder $excelBuilder,
        private readonly LaporanRegistryService $registryService,
    ) {}

    public function generate(User $petugas, int $tahun, int $bulan): void
    {
        $detail = $this->reportDataService->detailForReport($tahun, $bulan);
        $periodeKey = IpamBulan::periodeKey($tahun, $bulan);
        $relativePath = $this->buildRelativePath($petugas->id, $periodeKey);

        $this->excelBuilder->build($detail, $relativePath);

        $this->registryService->registerXlsx(
            $petugas,
            LaporanGenerated::JENIS_IPAM,
            self::periodeLabel($tahun, $bulan),
            $relativePath,
        );
    }

    public function regenerateFromRecord(LaporanGenerated $laporan): void
    {
        $user = $laporan->user;

        if ($user === null) {
            return;
        }

        $periode = $this->extractPeriodeFromPath($laporan->file_path_xlsx);

        if ($periode === null) {
            return;
        }

        $this->generate($user, $periode['tahun'], $periode['bulan']);
    }

    public function extractPeriodeFromPath(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/pemantauan/ipam/\d+/(\d{4})-(\d{1,2})\.xlsx$#', $path, $matches) !== 1) {
            return null;
        }

        return [
            'tahun' => (int) $matches[1],
            'bulan' => (int) $matches[2],
        ];
    }

    private function buildRelativePath(int $userId, string $periodeKey): string
    {
        return sprintf('laporan/pemantauan/ipam/%d/%s.xlsx', $userId, $periodeKey);
    }

    public static function periodeLabel(int $tahun, int $bulan): string
    {
        return sprintf('%s %d', IpamBulan::bulanNameFromNumber($bulan), $tahun);
    }
}
