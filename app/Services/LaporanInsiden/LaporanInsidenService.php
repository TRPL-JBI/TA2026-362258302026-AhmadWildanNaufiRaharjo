<?php

namespace App\Services\LaporanInsiden;

use App\Models\LaporanInsiden;
use App\Models\Lokasi;
use App\Models\Notifikasi;
use App\Models\TindakLanjutInsiden;
use App\Models\User;
use App\Notifications\WebPushAlertNotification;
use App\Services\PhotoStorageService;
use App\Support\LaporanInsidenKorban;
use App\Support\WebPushConfig;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LaporanInsidenService
{
    private const FOTO_DIRECTORY = 'laporan-insiden';

    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly LaporanInsidenLaporanGenerateService $laporanGenerateService,
    ) {}

    /**
     * @return list<array{id: int, label: string}>
     */
    public function lokasiOptionsForForm(): array
    {
        return Lokasi::query()
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi'])
            ->map(fn ($lokasi) => [
                'id' => $lokasi->id,
                'label' => $lokasi->nama_lokasi,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $fotoFiles
     * @return array{id: int, nomor: string, tanggal_waktu: string}
     */
    public function store(User $satpam, array $data, array $fotoFiles): array
    {
        if ($fotoFiles === []) {
            throw ValidationException::withMessages([
                'foto' => 'Minimal satu foto TKP wajib diunggah.',
            ]);
        }

        $tanggalWaktu = Carbon::parse($data['tanggal'].' '.$data['waktu']);

        return DB::transaction(function () use ($satpam, $data, $fotoFiles, $tanggalWaktu) {
            $paths = $this->photoStorage->storePatroliPhotos($fotoFiles, self::FOTO_DIRECTORY);
            $fotoPath = $this->photoStorage->encodePaths($paths);

            $korbanList = LaporanInsidenKorban::normalizeList(
                is_array($data['korban_list'] ?? null) ? $data['korban_list'] : [],
            );
            $firstKorban = $korbanList[0] ?? null;

            $laporan = LaporanInsiden::query()->create([
                'satpam_id' => $satpam->id,
                'lokasi_id' => $data['lokasi_id'] ?? null,
                'lokasi_manual' => isset($data['lokasi_manual']) ? trim((string) $data['lokasi_manual']) : null,
                'jenis_insiden' => $data['jenis_insiden'],
                'tanggal_waktu' => $tanggalWaktu,
                'kronologi' => trim((string) $data['kronologi']),
                'korban' => LaporanInsidenKorban::encode($korbanList),
                // Denormalisasi korban pertama untuk kompatibilitas tampilan lama.
                'usia_korban' => $firstKorban['usia'] ?? null,
                'unit_prodi' => $firstKorban['unit_prodi'] ?? null,
                'jabatan_korban' => $firstKorban['jabatan'] ?? null,
                'status_korban' => $firstKorban['status'] ?? null,
                'foto_path' => $fotoPath,
            ]);

            TindakLanjutInsiden::query()->create([
                'laporan_insiden_id' => $laporan->id,
                'status_perbaikan' => 'Dalam Proses',
            ]);

            $this->notifyPetugasK3lh($laporan);
            $this->laporanGenerateService->generate($satpam, $laporan->fresh(['lokasi', 'satpam']));

            return [
                'id' => $laporan->id,
                'nomor' => 'INS-'.str_pad((string) $laporan->id, 5, '0', STR_PAD_LEFT),
                'tanggal_waktu' => $laporan->tanggal_waktu->toIso8601String(),
            ];
        });
    }

    private function notifyPetugasK3lh(LaporanInsiden $laporan): void
    {
        $laporan->loadMissing(['lokasi', 'satpam']);

        $lokasiLabel = $laporan->lokasi
            ? $laporan->lokasi->nama_lokasi
            : ($laporan->lokasi_manual ?? 'Lokasi tidak diketahui');

        $judul = 'Laporan Insiden Darurat: '.$laporan->jenis_insiden;
        $pesan = sprintf(
            '%s melaporkan insiden %s di %s pada %s. Segera tinjau di menu Tindak Lanjut.',
            $laporan->satpam?->nama_lengkap ?? 'Satpam',
            $laporan->jenis_insiden,
            $lokasiLabel,
            $laporan->tanggal_waktu->timezone(config('app.timezone'))->format('d M Y H:i'),
        );

        $petugasUsers = User::query()
            ->where('role', 'Petugas K3LH')
            ->where('is_active', true)
            ->with('pushSubscriptions')
            ->get();

        $actionUrl = route('tindak-lanjut');

        foreach ($petugasUsers as $petugas) {
            Notifikasi::query()->create([
                'user_id' => $petugas->id,
                'jenis_notifikasi' => 'Laporan Insiden',
                'judul' => $judul,
                'pesan' => $pesan,
                'reference_id' => $laporan->id,
                'is_read' => false,
            ]);

            $this->sendWebPush($petugas, $judul, $pesan, $actionUrl);
        }
    }

    private function sendWebPush(User $user, string $judul, string $pesan, string $url): void
    {
        if (! WebPushConfig::isConfigured() || $user->pushSubscriptions->isEmpty()) {
            return;
        }

        try {
            $user->notify(new WebPushAlertNotification($judul, $pesan, $url));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
