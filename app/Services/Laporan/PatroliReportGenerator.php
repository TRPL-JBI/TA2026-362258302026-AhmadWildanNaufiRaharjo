<?php

namespace App\Services\Laporan;

use App\Models\LaporanGenerated;
use App\Models\PatroliLaporanPeriode;
use App\Models\User;
use App\Support\PatroliPeriode;

class PatroliReportGenerator
{
    public function __construct(
        private readonly PatroliTemuanWordBuilder $temuanWordBuilder,
        private readonly PatroliAparExcelBuilder $aparExcelBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $detail
     */
    public function generateTemuan(User $petugas, string $periodeKey, array $detail): string
    {
        $relativePath = $this->buildRelativePath('k3l', $petugas->id, $periodeKey, 'docx');

        $this->temuanWordBuilder->build($petugas, $periodeKey, $detail, $relativePath);

        return $relativePath;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    public function generateApar(User $petugas, string $periodeKey, array $detail): string
    {
        $relativePath = $this->buildRelativePath('apar', $petugas->id, $periodeKey, 'xlsx');

        $this->aparExcelBuilder->build($petugas, $detail, $relativePath);

        return $relativePath;
    }

    private function buildRelativePath(string $folder, int $userId, string $periodeKey, string $extension): string
    {
        return sprintf('laporan/patroli/%s/%d/%s.%s', $folder, $userId, $periodeKey, $extension);
    }

    public static function jenisLaporanForPatroli(string $jenis): string
    {
        return $jenis === PatroliLaporanPeriode::JENIS_APAR
            ? LaporanGenerated::JENIS_INVENTARIS_APAR
            : LaporanGenerated::JENIS_K3L;
    }

    public static function periodeLabel(string $periodeKey): string
    {
        return PatroliPeriode::label($periodeKey);
    }
}
