<?php

namespace App\Services\Laporan;

use App\Models\LaporanGenerated;
use Illuminate\Support\Facades\Storage;

class LaporanService
{
    public function displayNameFor(LaporanGenerated $row): string
    {
        return $this->displayName($row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRows(): array
    {
        return LaporanGenerated::query()
            ->with('user:id,nama_lengkap')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LaporanGenerated $row) => $this->serializeRow($row))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRow(LaporanGenerated $row): array
    {
        $format = $row->file_path_xlsx !== null ? '.xlsx' : '.docx';
        $path = $row->file_path_xlsx ?? $row->file_path_docx;
        $sizeBytes = $path !== null ? Storage::disk('local')->size($path) : 0;

        return [
            'id' => $row->id,
            'nama' => $this->displayName($row),
            'jenis' => $this->displayJenis($row->jenis_laporan),
            'tanggal' => $row->created_at?->format('d/m/Y') ?? '-',
            'format' => $format,
            'size' => $this->formatSize($sizeBytes),
            'download_url' => route('laporan.download', $row),
            'preview_url' => route('laporan.preview', $row),
            'petugas' => $row->user?->nama_lengkap ?? '-',
        ];
    }

    private function displayName(LaporanGenerated $row): string
    {
        return match ($row->jenis_laporan) {
            LaporanGenerated::JENIS_K3L => 'Laporan Patroli K3LH - '.$row->periode,
            LaporanGenerated::JENIS_INVENTARIS_APAR => 'Laporan Inventaris APAR - '.$row->periode,
            LaporanGenerated::JENIS_IPAM => 'Laporan Rekap IPAM - '.$row->periode,
            LaporanGenerated::JENIS_B3 => 'Laporan Swapantau B3 - '.$row->periode,
            LaporanGenerated::JENIS_IPAL => 'Laporan Swapantau IPAL - '.$row->periode,
            LaporanGenerated::JENIS_TINDAK_LANJUT => 'Laporan Rekap Tindak Lanjut - '.$row->periode,
            LaporanGenerated::JENIS_INSIDEN => 'Laporan Insiden - '.$row->periode,
            default => $row->jenis_laporan.' - '.$row->periode,
        };
    }

    private function displayJenis(string $jenisLaporan): string
    {
        return match ($jenisLaporan) {
            LaporanGenerated::JENIS_K3L => 'Patroli',
            LaporanGenerated::JENIS_INVENTARIS_APAR => 'APAR',
            LaporanGenerated::JENIS_IPAM => 'IPAM',
            LaporanGenerated::JENIS_B3 => 'B3',
            LaporanGenerated::JENIS_IPAL => 'IPAL',
            LaporanGenerated::JENIS_TINDAK_LANJUT => 'Tindak Lanjut',
            LaporanGenerated::JENIS_INSIDEN => 'Insiden',
            default => $jenisLaporan,
        };
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
