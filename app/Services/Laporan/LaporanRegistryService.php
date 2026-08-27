<?php

namespace App\Services\Laporan;

use App\Models\LaporanGenerated;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class LaporanRegistryService
{
    public function registerDocx(User $user, string $jenisLaporan, string $periodeLabel, string $relativePath): LaporanGenerated
    {
        $existing = $this->findExisting($user, $jenisLaporan, $periodeLabel);

        if ($existing !== null) {
            $this->deleteStoredFile($existing->file_path_docx, $relativePath);
            $this->deleteStoredFile($existing->file_path_xlsx, null);
        }

        return LaporanGenerated::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'jenis_laporan' => $jenisLaporan,
                'periode' => $periodeLabel,
            ],
            [
                'file_path_docx' => $relativePath,
                'file_path_xlsx' => null,
                'created_at' => now(),
            ],
        );
    }

    public function registerXlsx(User $user, string $jenisLaporan, string $periodeLabel, string $relativePath): LaporanGenerated
    {
        $existing = $this->findExisting($user, $jenisLaporan, $periodeLabel);

        if ($existing !== null) {
            $this->deleteStoredFile($existing->file_path_xlsx, $relativePath);
            $this->deleteStoredFile($existing->file_path_docx, null);
        }

        return LaporanGenerated::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'jenis_laporan' => $jenisLaporan,
                'periode' => $periodeLabel,
            ],
            [
                'file_path_docx' => null,
                'file_path_xlsx' => $relativePath,
                'created_at' => now(),
            ],
        );
    }

    public function deleteDocx(User $user, string $jenisLaporan, string $periodeLabel): void
    {
        $record = $this->findExisting($user, $jenisLaporan, $periodeLabel);

        if ($record === null) {
            return;
        }

        $this->deleteStoredFile($record->file_path_docx, null);
        $this->deleteStoredFile($record->file_path_xlsx, null);

        $record->delete();
    }

    private function findExisting(User $user, string $jenisLaporan, string $periodeLabel): ?LaporanGenerated
    {
        return LaporanGenerated::query()
            ->where('user_id', $user->id)
            ->where('jenis_laporan', $jenisLaporan)
            ->where('periode', $periodeLabel)
            ->first();
    }

    private function deleteStoredFile(?string $storedPath, ?string $replacementPath): void
    {
        if ($storedPath === null || $storedPath === '' || $storedPath === $replacementPath) {
            return;
        }

        Storage::disk('local')->delete($storedPath);
    }
}
