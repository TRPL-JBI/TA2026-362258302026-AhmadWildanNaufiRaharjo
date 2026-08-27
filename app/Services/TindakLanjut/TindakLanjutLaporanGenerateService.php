<?php

namespace App\Services\TindakLanjut;

use App\Models\LaporanGenerated;
use App\Models\User;
use App\Services\Laporan\LaporanRegistryService;
use App\Services\Laporan\TindakLanjutWordBuilder;
use App\Support\PatroliPeriode;

class TindakLanjutLaporanGenerateService
{
    public function __construct(
        private readonly TindakLanjutWordBuilder $wordBuilder,
        private readonly LaporanRegistryService $registryService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function generateForPeriode(User $petugas, string $periodeKey, array $items): void
    {
        $relativePath = sprintf('laporan/tindak-lanjut/%d/%s.docx', $petugas->id, $periodeKey);
        $this->wordBuilder->buildPeriode($petugas, $periodeKey, $items, $relativePath);

        $this->registryService->registerDocx(
            $petugas,
            LaporanGenerated::JENIS_TINDAK_LANJUT,
            PatroliPeriode::label($periodeKey),
            $relativePath,
        );
    }

    /**
     * Laporan tindak lanjut periode adalah arsip snapshot dan tidak di-regenerate.
     */
    public function regenerateFromRecord(LaporanGenerated $laporan): void
    {
        // Snapshot dibekukan saat periode ditutup.
    }

    public function extractPeriodeKeyFromPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/tindak-lanjut/\d+/(\d{4}-[1-3])\.docx$#', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
