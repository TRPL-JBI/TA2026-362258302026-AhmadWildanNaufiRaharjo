<?php

namespace App\Services\LaporanInsiden;

use App\Models\LaporanGenerated;
use App\Models\LaporanInsiden;
use App\Models\User;
use App\Services\Laporan\LaporanInsidenWordBuilder;
use App\Services\Laporan\LaporanRegistryService;
use App\Services\PhotoStorageService;
use App\Support\LaporanInsidenKorban;

class LaporanInsidenLaporanGenerateService
{
    public function __construct(
        private readonly LaporanInsidenWordBuilder $wordBuilder,
        private readonly LaporanRegistryService $registryService,
        private readonly PhotoStorageService $photoStorage,
    ) {}

    public function generate(User $satpam, LaporanInsiden $laporan): void
    {
        $laporan->loadMissing(['lokasi', 'satpam']);

        $relativePath = $this->buildRelativePath($satpam->id, $laporan->id);
        $detail = $this->detailForReport($laporan);

        $this->wordBuilder->build($detail, $relativePath);

        $this->registryService->registerDocx(
            $satpam,
            LaporanGenerated::JENIS_INSIDEN,
            (string) $detail['registry_periode_label'],
            $relativePath,
        );
    }

    public function regenerateFromRecord(LaporanGenerated $laporanGenerated): void
    {
        $user = $laporanGenerated->user;

        if ($user === null) {
            return;
        }

        $laporanId = $this->extractLaporanIdFromPath(
            $laporanGenerated->file_path_docx ?? $laporanGenerated->file_path_xlsx,
        );

        if ($laporanId === null) {
            return;
        }

        $laporan = LaporanInsiden::query()->find($laporanId);

        if ($laporan === null) {
            return;
        }

        $this->generate($user, $laporan);
    }

    public function extractLaporanIdFromPath(?string $path): ?int
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#laporan/insiden/\d+/(\d+)\.(docx|xlsx)$#', $path, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailForReport(LaporanInsiden $laporan): array
    {
        $lokasi = $laporan->lokasi?->nama_lokasi
            ?? ($laporan->lokasi_manual ?: '-');

        $tanggal = $laporan->tanggal_waktu
            ? $laporan->tanggal_waktu->timezone(config('app.timezone'))->format('d-m-Y')
            : '-';

        $jam = $laporan->tanggal_waktu
            ? $laporan->tanggal_waktu->timezone(config('app.timezone'))->format('H.i')
            : '-';

        $nomor = 'INS-'.str_pad((string) $laporan->id, 5, '0', STR_PAD_LEFT);

        $korbanList = LaporanInsidenKorban::decode(
            $laporan->korban,
            $laporan->usia_korban,
            $laporan->unit_prodi,
            $laporan->jabatan_korban,
            $laporan->status_korban,
        );

        return [
            'jenis_insiden' => $laporan->jenis_insiden,
            'korban_list' => $korbanList,
            'tanggal' => $tanggal,
            'jam' => $jam,
            'lokasi' => $lokasi,
            'kronologi' => $laporan->kronologi,
            'foto_paths' => $this->photoStorage->decodePaths($laporan->foto_path),
            'dibuat_oleh' => $laporan->satpam?->nama_lengkap ?? '-',
            'registry_periode_label' => $nomor.' · '.$tanggal,
        ];
    }

    private function buildRelativePath(int $userId, int $laporanId): string
    {
        return sprintf('laporan/insiden/%d/%d.docx', $userId, $laporanId);
    }
}
