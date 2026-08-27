<?php

namespace App\Services;

use App\Models\Apar;
use App\Models\Lokasi;
use Illuminate\Support\Facades\Storage;

class AparQrCodeService
{
    private const DISK = 'public';

    private const DIRECTORY = 'qr/apar';

    public function __construct(
        private readonly QrCodeBrandingService $brandingService,
    ) {}

    /**
     * Isi QR: JSON ringkas (type, id, kode) — sama pola checklist temuan/lokasi.
     */
    public function content(Apar $apar): string
    {
        return json_encode([
            'type' => 'apar',
            'id' => $apar->id,
            'kode' => $apar->kode_apar,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Metadata APAR (untuk keperluan lain, bukan isi QR).
     *
     * @return array<string, mixed>
     */
    public function payload(Apar $apar): array
    {
        $apar->loadMissing('lokasi');
        $lokasi = $apar->lokasi;

        return [
            'type' => 'apar',
            'id' => $apar->id,
            'kode' => $apar->kode_apar,
            'kode_apar' => $apar->kode_apar,
            'kodeApar' => $apar->kode_apar,
            'lokasiApar' => $lokasi?->nama_lokasi,
            'lokasi_apar' => $lokasi?->nama_lokasi,
            'nama_lokasi' => $lokasi?->nama_lokasi,
            'jenisKapasitas' => $apar->jenisKapasitasLabel(),
            'tanggalExpired' => $apar->tanggal_expired->format('Y-m-d'),
        ];
    }

    public function generate(Apar $apar): string
    {
        $this->deleteFile($apar->qr_code_path);

        $relativePath = self::DIRECTORY.'/'.$apar->id.'.svg';
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);

        Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);

        $this->brandingService->writeBrandedSvg($this->content($apar), $absolutePath);

        return $relativePath;
    }

    public function deleteFile(?string $relativePath): void
    {
        if ($relativePath !== null && $relativePath !== '' && Storage::disk(self::DISK)->exists($relativePath)) {
            Storage::disk(self::DISK)->delete($relativePath);
        }
    }

    public function exists(?string $relativePath): bool
    {
        return $relativePath !== null
            && $relativePath !== ''
            && Storage::disk(self::DISK)->exists($relativePath);
    }

    public static function generateKodeApar(Lokasi $lokasi): string
    {
        $prefix = explode('-', $lokasi->kode_lokasi, 2)[0] ?? 'LOK';
        $pattern = 'APAR-'.$prefix.'-%';

        $lastNumber = Apar::query()
            ->where('kode_apar', 'like', $pattern)
            ->pluck('kode_apar')
            ->map(function (string $kode) use ($prefix): int {
                if (preg_match('/^APAR-'.preg_quote($prefix, '/').'-(\d+)$/', $kode, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return sprintf('APAR-%s-%03d', $prefix, $lastNumber + 1);
    }
}
