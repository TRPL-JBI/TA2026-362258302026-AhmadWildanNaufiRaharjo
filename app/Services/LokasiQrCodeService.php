<?php

namespace App\Services;

use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;

class LokasiQrCodeService
{
    private const DISK = 'public';

    private const DIRECTORY = 'qr/lokasi';

    public function __construct(
        private readonly QrCodeBrandingService $brandingService,
    ) {}

    /**
     * Isi QR
     */
    public function content(Lokasi $lokasi): string
    {
        return json_encode([
            'type' => 'lokasi',
            'id' => $lokasi->id,
            'kode' => $lokasi->kode_lokasi,
            'nama' => $lokasi->nama_lokasi,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Metadata lokasi (untuk keperluan lain, bukan isi QR).
     *
     * @return array{type: string, id: int, kode: string, nama: string, nama_lokasi: string}
     */
    public function payload(Lokasi $lokasi): array
    {
        return [
            'type' => 'lokasi',
            'id' => $lokasi->id,
            'kode' => $lokasi->kode_lokasi,
            'nama' => $lokasi->nama_lokasi,
            'nama_lokasi' => $lokasi->nama_lokasi,
        ];
    }

    public function generate(Lokasi $lokasi): string
    {
        $this->deleteFile($lokasi->qr_code_path);

        $relativePath = self::DIRECTORY.'/'.$lokasi->id.'.svg';
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);

        Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);

        $this->brandingService->writeBrandedSvg($this->content($lokasi), $absolutePath);

        return $relativePath;
    }

    public function deleteFile(?string $relativePath): void
    {
        if ($relativePath !== null && $relativePath !== '' && Storage::disk(self::DISK)->exists($relativePath)) {
            Storage::disk(self::DISK)->delete($relativePath);
        }
    }

    
    public function publicPath(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return '/storage/'.ltrim($relativePath, '/');
    }

    public function exists(?string $relativePath): bool
    {
        return $relativePath !== null
            && $relativePath !== ''
            && Storage::disk(self::DISK)->exists($relativePath);
    }

    public static function generateKodeLokasi(string $jenisLokasi): string
    {
        $prefix = match ($jenisLokasi) {
            'Gedung' => 'GED',
            'Laboratorium' => 'LAB',
            'Ruangan' => 'RU',
            default => 'LOK',
        };

        $lastNumber = Lokasi::query()
            ->where('kode_lokasi', 'like', $prefix.'-%')
            ->pluck('kode_lokasi')
            ->map(function (string $kode) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $kode, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $lastNumber + 1);
    }
}
