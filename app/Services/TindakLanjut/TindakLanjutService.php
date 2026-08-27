<?php

namespace App\Services\TindakLanjut;

use App\Models\DetailInspeksi;
use App\Models\LaporanInsiden;
use App\Models\TindakLanjutInsiden;
use App\Models\TindakLanjutInspeksi;
use App\Models\TindakLanjutLaporanPeriode;
use App\Models\User;
use App\Services\PhotoStorageService;
use App\Support\PatroliPeriode;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TindakLanjutService
{
    private const FOTO_DIRECTORY = 'tindak-lanjut';

    public function __construct(
        private readonly PhotoStorageService $photoStorage,
        private readonly TindakLanjutPeriodeService $periodeService,
        private readonly TindakLanjutLaporanGenerateService $laporanGenerate,
        private readonly TindakLanjutLaporanPeriodeService $laporanPeriodeService,
    ) {}

    /**
     * @return list<array{
     *   uid: string,
     *   ref_type: 'inspeksi'|'insiden',
     *   ref_id: int,
     *   jenis: string,
     *   tanggal: string,
     *   lokasi: string,
     *   deskripsi: string,
     *   risiko: string,
     *   skor: ?int,
     *   status: string,
     *   foto_dokumentasi: list<array{id: string, preview: string, storedPath: string, existing: bool}>,
     *   foto_bukti: list<array{id: string, preview: string, storedPath: string, existing: bool}>,
     *   periode_asal: string,
     *   periode_asal_label: string,
     *   is_carry_over: bool,
     *   catatan: ?string,
     * }>
     */
    public function listItemsForPeriode(string $periode): array
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            $periode = PatroliPeriode::keyFromDate(now());
        }

        if ($this->laporanPeriodeService->isSelesai($periode)) {
            $snapshot = $this->laporanPeriodeService->itemsSnapshot($periode);

            if ($snapshot !== null) {
                return $snapshot;
            }
        }

        return $this->buildLiveItemsForPeriode($periode);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLiveItemsForPeriode(string $periode): array
    {
        $insidenItems = $this->listInsidenItems($periode);
        $inspeksiItems = $this->listInspeksiItems($periode);

        $items = array_merge($insidenItems, $inspeksiItems);

        usort($items, function (array $a, array $b) {
            // Global: item Selesai selalu di bawah yang belum selesai
            $ca = $this->completionWeight($a['status'] ?? '');
            $cb = $this->completionWeight($b['status'] ?? '');
            if ($ca !== $cb) {
                return $ca <=> $cb;
            }

            $pa = $this->priorityWeight($a);
            $pb = $this->priorityWeight($b);
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            $sa = $this->statusWeight($a['status'] ?? '');
            $sb = $this->statusWeight($b['status'] ?? '');
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return strcmp((string) ($b['_sort_tanggal'] ?? ''), (string) ($a['_sort_tanggal'] ?? ''));
        });

        foreach ($items as &$item) {
            unset($item['_sort_tanggal']);
        }

        return $items;
    }

    /**
     * @return array{
     *   status: string,
     *   total: int,
     *   selesai: int,
     *   can_finish: bool,
     *   is_locked: bool,
     * }
     */
    public function periodeState(string $periode): array
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            $periode = PatroliPeriode::keyFromDate(now());
        }

        $items = $this->listItemsForPeriode($periode);
        $total = count($items);
        $selesai = count(array_filter($items, fn (array $item) => ($item['status'] ?? '') === 'Selesai'));
        $dalamProses = count(array_filter($items, fn (array $item) => ($item['status'] ?? '') === 'Dalam Proses'));
        $menunggu = count(array_filter($items, fn (array $item) => ($item['status'] ?? '') === 'Menunggu Tindakan'));
        $isLocked = $this->laporanPeriodeService->isSelesai($periode);

        return [
            'status' => $isLocked
                ? TindakLanjutLaporanPeriode::STATUS_SELESAI
                : TindakLanjutLaporanPeriode::STATUS_BERLANGSUNG,
            'total' => $total,
            'selesai' => $selesai,
            'dalam_proses' => $dalamProses,
            'menunggu' => $menunggu,
            'can_finish' => ! $isLocked && $total > 0,
            'is_locked' => $isLocked,
        ];
    }

    /**
     * @return array{status: string, redirect: string}
     */
    public function markPeriodeSelesai(User $petugas, string $periode): array
    {
        if (! PatroliPeriode::isValidKey($periode)) {
            throw ValidationException::withMessages([
                'periode' => 'Periode tindak lanjut tidak valid.',
            ]);
        }

        $items = $this->buildLiveItemsForPeriode($periode);

        if ($items === []) {
            throw ValidationException::withMessages([
                'periode' => 'Tidak ada item tindak lanjut pada periode ini.',
            ]);
        }

        $this->laporanPeriodeService->markSelesai($petugas, $periode, $items);
        $this->laporanGenerate->generateForPeriode($petugas, $periode, $items);

        return [
            'status' => TindakLanjutLaporanPeriode::STATUS_SELESAI,
            'redirect' => route('tindak-lanjut', ['periode' => $periode], false),
        ];
    }

    /**
     * @param  array{status: string, tanggal_mulai?: ?string, tanggal_selesai?: ?string, catatan?: ?string}  $data
     * @return array{status: string, foto_path: ?string, catatan: ?string, tanggal_mulai: ?string, tanggal_selesai: ?string}
     */
    public function updateInspeksi(User $petugas, DetailInspeksi $detail, array $data, ?UploadedFile $foto): array
    {
        $this->assertCanUpdateInspeksi($detail);

        return DB::transaction(function () use ($petugas, $detail, $data, $foto) {
            $tl = TindakLanjutInspeksi::query()->firstOrNew([
                'detail_inspeksi_id' => $detail->id,
            ]);

            $targetStatus = (string) ($data['status'] ?? '');

            $tl->petugas_id = $petugas->id;

            $this->applyTanggalInput($tl, $data['tanggal_mulai'] ?? null, $data['tanggal_selesai'] ?? null);
            $this->applyStatusAndTanggal($tl, $targetStatus);
            $tl->catatan_perbaikan = isset($data['catatan']) && trim((string) $data['catatan']) !== ''
                ? trim((string) $data['catatan'])
                : null;

            if ($foto instanceof UploadedFile) {
                $tl->foto_bukti_path = $this->photoStorage->storePatroliPhoto(
                    $foto,
                    self::FOTO_DIRECTORY.'/inspeksi/'.now()->format('Y/m'),
                );
            }

            $tl->save();

            return [
                'status' => $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan),
                'foto_path' => $tl->foto_bukti_path,
                'catatan' => $tl->catatan_perbaikan,
                'tanggal_mulai' => $tl->tanggal_tindakan?->toDateString(),
                'tanggal_selesai' => $tl->tanggal_selesai?->toDateString(),
            ];
        });
    }

    /**
     * @param  array{status: string, tanggal_mulai?: ?string, tanggal_selesai?: ?string, catatan?: ?string}  $data
     * @return array{status: string, foto_path: ?string, catatan: ?string, tanggal_mulai: ?string, tanggal_selesai: ?string}
     */
    public function updateInsiden(User $petugas, LaporanInsiden $laporan, array $data, ?UploadedFile $foto): array
    {
        $this->assertCanUpdateInsiden($laporan);

        return DB::transaction(function () use ($petugas, $laporan, $data, $foto) {
            $tl = TindakLanjutInsiden::query()->firstOrNew([
                'laporan_insiden_id' => $laporan->id,
            ]);

            $targetStatus = (string) ($data['status'] ?? '');

            $tl->petugas_id = $petugas->id;

            $this->applyTanggalInput($tl, $data['tanggal_mulai'] ?? null, $data['tanggal_selesai'] ?? null);
            $this->applyStatusAndTanggal($tl, $targetStatus);
            $tl->catatan_perbaikan = isset($data['catatan']) && trim((string) $data['catatan']) !== ''
                ? trim((string) $data['catatan'])
                : null;

            if ($foto instanceof UploadedFile) {
                $tl->foto_bukti_path = $this->photoStorage->storePatroliPhoto(
                    $foto,
                    self::FOTO_DIRECTORY.'/insiden/'.now()->format('Y/m'),
                );
            }

            $tl->save();

            return [
                'status' => $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan),
                'foto_path' => $tl->foto_bukti_path,
                'catatan' => $tl->catatan_perbaikan,
                'tanggal_mulai' => $tl->tanggal_tindakan?->toDateString(),
                'tanggal_selesai' => $tl->tanggal_selesai?->toDateString(),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listInsidenItems(string $viewPeriode): array
    {
        return LaporanInsiden::query()
            ->with(['lokasi', 'satpam:id,nama_lengkap', 'tindakLanjut'])
            ->orderByDesc('tanggal_waktu')
            ->get()
            ->map(function (LaporanInsiden $laporan) use ($viewPeriode) {
                $lokasiLabel = $laporan->lokasi
                    ? $laporan->lokasi->nama_lokasi
                    : ($laporan->lokasi_manual ?? 'Lokasi tidak diketahui');

                $tl = $laporan->tindakLanjut;
                $status = $tl
                    ? $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan)
                    : 'Menunggu Tindakan';
                $periodeAsal = $this->periodeService->originKeyFromInsiden($laporan);

                $item = [
                    'uid' => 'insiden-'.$laporan->id,
                    'ref_type' => 'insiden',
                    'ref_id' => $laporan->id,
                    'jenis' => 'Laporan Insiden Darurat (Satpam)',
                    'tanggal' => $laporan->tanggal_waktu
                        ? $laporan->tanggal_waktu->timezone(config('app.timezone'))->format('d M Y H:i')
                        : '-',
                    'tanggal_list' => $laporan->tanggal_waktu
                        ? $laporan->tanggal_waktu->timezone(config('app.timezone'))->format('d M Y')
                        : '-',
                    'lokasi' => $lokasiLabel,
                    'deskripsi' => 'Insiden: '.$laporan->jenis_insiden,
                    'jenis_insiden' => $laporan->jenis_insiden,
                    'korban' => $laporan->korban,
                    'usia_korban' => $laporan->usia_korban,
                    'unit_prodi' => $laporan->unit_prodi,
                    'jabatan_korban' => $laporan->jabatan_korban,
                    'status_korban' => $laporan->status_korban,
                    'pelapor' => $laporan->satpam?->nama_lengkap,
                    'risiko' => 'Darurat',
                    'skor' => null,
                    'status' => $status,
                    'foto_dokumentasi' => $this->photoStorage->fotoEntriesFromStored($laporan->foto_path),
                    'foto_bukti' => $this->photoStorage->fotoEntriesFromStored($tl?->foto_bukti_path),
                    'catatan' => $tl?->catatan_perbaikan,
                    'kronologi' => $laporan->kronologi,
                    'tanggal_mulai' => $tl?->tanggal_tindakan?->toDateString(),
                    'tanggal_selesai' => $tl?->tanggal_selesai?->toDateString(),
                    'periode_asal' => $periodeAsal,
                    'periode_asal_label' => PatroliPeriode::displayLabel($periodeAsal),
                    '_sort_tanggal' => $laporan->tanggal_waktu?->toIso8601String(),
                ];

                return $this->finalizeItemForPeriode($item, $viewPeriode);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listInspeksiItems(string $viewPeriode): array
    {
        return DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->with(['inspeksi.lokasi', 'itemChecklist', 'tindakLanjut'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DetailInspeksi $detail) use ($viewPeriode) {
                $lokasiLabel = $detail->inspeksi?->lokasi?->nama_lokasi ?? '-';
                $itemBahaya = $detail->itemChecklist?->nama_item ?? '-';

                $tl = $detail->tindakLanjut;
                $status = $tl
                    ? $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan)
                    : 'Menunggu Tindakan';
                $periodeAsal = $this->periodeService->originKeyFromInspeksi($detail);

                $item = [
                    'uid' => 'inspeksi-'.$detail->id,
                    'ref_type' => 'inspeksi',
                    'ref_id' => $detail->id,
                    'jenis' => 'Temuan Patroli',
                    'tanggal' => $detail->inspeksi?->tanggal_inspeksi
                        ? $detail->inspeksi->tanggal_inspeksi->timezone(config('app.timezone'))->format('d M Y H:i')
                        : ($detail->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '-'),
                    'tanggal_list' => $detail->inspeksi?->tanggal_inspeksi
                        ? $detail->inspeksi->tanggal_inspeksi->timezone(config('app.timezone'))->format('d M Y')
                        : ($detail->created_at?->timezone(config('app.timezone'))->format('d M Y') ?? '-'),
                    // NOTE: permintaan user: deskripsi list = nama item bahaya
                    'lokasi' => $lokasiLabel,
                    'deskripsi' => $itemBahaya,
                    'risiko' => $detail->level_risiko_hasil ?? 'Rendah',
                    'skor' => $detail->skor_risiko_hasil,
                    'status' => $status,
                    'foto_dokumentasi' => $this->photoStorage->fotoEntriesFromStored($detail->foto_path),
                    'foto_bukti' => $this->photoStorage->fotoEntriesFromStored($tl?->foto_bukti_path),
                    'catatan' => $tl?->catatan_perbaikan,
                    'analisa_risiko' => $detail->analisa_risiko,
                    'rekomendasi' => $detail->rekomendasi,
                    'tanggal_mulai' => $tl?->tanggal_tindakan?->toDateString(),
                    'tanggal_selesai' => $tl?->tanggal_selesai?->toDateString(),
                    'periode_asal' => $periodeAsal,
                    'periode_asal_label' => PatroliPeriode::displayLabel($periodeAsal),
                    '_sort_tanggal' => $detail->inspeksi?->tanggal_inspeksi?->toIso8601String()
                        ?? $detail->created_at?->toIso8601String(),
                ];

                return $this->finalizeItemForPeriode($item, $viewPeriode);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function finalizeItemForPeriode(array $item, string $viewPeriode): ?array
    {
        if (! $this->periodeService->isVisibleInPeriode($item, $viewPeriode)) {
            return null;
        }

        $origin = (string) ($item['periode_asal'] ?? '');
        $item['is_carry_over'] = ($item['status'] ?? '') !== 'Selesai'
            && $this->periodeService->compareKeys($origin, $viewPeriode) < 0;

        return $item;
    }

    /**
     * 0 = paling atas.
     */
    private function priorityWeight(array $item): int
    {
        if (($item['ref_type'] ?? null) === 'insiden') {
            return 0;
        }

        return match ($item['risiko'] ?? '') {
            'Sangat Tinggi' => 1,
            'Tinggi' => 2,
            'Sedang' => 3,
            'Rendah' => 4,
            default => 5,
        };
    }

    /**
     * 0 = paling atas.
     */
    private function statusWeight(string $status): int
    {
        return match ($status) {
            'Menunggu Tindakan' => 0,
            'Dalam Proses' => 1,
            'Selesai' => 2,
            default => 3,
        };
    }

    /**
     * 0 = belum selesai (atas), 1 = selesai (bawah).
     */
    private function completionWeight(string $status): int
    {
        return $status === 'Selesai' ? 1 : 0;
    }

    private function displayStatus(string $statusPerbaikan, ?Carbon $tanggalTindakan): string
    {
        if ($statusPerbaikan === 'Selesai') {
            return 'Selesai';
        }

        return $tanggalTindakan ? 'Dalam Proses' : 'Menunggu Tindakan';
    }

    /**
     * @param  TindakLanjutInspeksi|TindakLanjutInsiden  $tl
     */
    private function applyStatusAndTanggal(object $tl, string $status): void
    {
        if ($status === 'Selesai') {
            $tl->status_perbaikan = 'Selesai';
            $tl->tanggal_tindakan = $tl->tanggal_tindakan ?? now();
            $tl->tanggal_selesai = $tl->tanggal_selesai ?? now();

            return;
        }

        $tl->status_perbaikan = 'Dalam Proses';
        $tl->tanggal_selesai = null;

        if ($status === 'Dalam Proses') {
            $tl->tanggal_tindakan = $tl->tanggal_tindakan ?? now();

            return;
        }

        // Menunggu Tindakan: tidak ada tanggal tindakan
        $tl->tanggal_tindakan = null;
        $tl->tanggal_selesai = null;
    }

    /**
     * @param  TindakLanjutInspeksi|TindakLanjutInsiden  $tl
     */
    private function applyTanggalInput(object $tl, ?string $mulai, ?string $selesai): void
    {
        $mulai = is_string($mulai) && trim($mulai) !== '' ? trim($mulai) : null;
        $selesai = is_string($selesai) && trim($selesai) !== '' ? trim($selesai) : null;

        if ($mulai !== null) {
            $tl->tanggal_tindakan = Carbon::parse($mulai)->startOfDay();
        }

        if ($selesai !== null) {
            $tl->tanggal_selesai = Carbon::parse($selesai)->startOfDay();
        }

        // Jika user isi tanggal selesai tapi belum isi mulai, otomatis set mulai = selesai
        if ($tl->tanggal_selesai !== null && $tl->tanggal_tindakan === null) {
            $tl->tanggal_tindakan = $tl->tanggal_selesai;
        }
    }

    private function assertCanUpdateInspeksi(DetailInspeksi $detail): void
    {
        $tl = $detail->tindakLanjut;

        if ($tl?->status_perbaikan !== 'Selesai' || $tl->tanggal_selesai === null) {
            return;
        }

        $periode = PatroliPeriode::keyFromDate(Carbon::parse($tl->tanggal_selesai));

        if ($this->laporanPeriodeService->isSelesai($periode)) {
            throw ValidationException::withMessages([
                'status' => 'Periode tindak lanjut sudah ditutup. Data tidak dapat diubah.',
            ]);
        }
    }

    private function assertCanUpdateInsiden(LaporanInsiden $laporan): void
    {
        $tl = $laporan->tindakLanjut;

        if ($tl?->status_perbaikan !== 'Selesai' || $tl->tanggal_selesai === null) {
            return;
        }

        $periode = PatroliPeriode::keyFromDate(Carbon::parse($tl->tanggal_selesai));

        if ($this->laporanPeriodeService->isSelesai($periode)) {
            throw ValidationException::withMessages([
                'status' => 'Periode tindak lanjut sudah ditutup. Data tidak dapat diubah.',
            ]);
        }
    }
}
