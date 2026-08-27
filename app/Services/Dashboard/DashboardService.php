<?php

namespace App\Services\Dashboard;

use App\Models\Apar;
use App\Models\DetailInspeksi;
use App\Models\LaporanInsiden;
use App\Models\MasterChecklist;
use App\Models\SopDokumen;
use App\Models\TindakLanjutInsiden;
use App\Models\TindakLanjutInspeksi;
use App\Models\User;
use App\Support\Caturwulan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const DONUT_COLORS = ['#3B82F6', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6'];

    private const PRIORITAS_LIMIT = 10;

    private const TIMELINE_LIMIT = 8;

    private const RISIKO_LOKASI_TOP = 5;

    private const SATPAM_RECENT_LIMIT = 8;

    private const KALAB_RECENT_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function dataForSatpamDashboard(User $satpam, ?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $timezone = config('app.timezone');

        return [
            'summary' => $this->buildSatpamSummary($satpam, $today),
            'laporanTerbaru' => $this->buildSatpamLaporanTerbaru($satpam, $timezone),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataForKalabDashboard(User $kalab, ?Carbon $today = null): array
    {
        $kalab->loadMissing('lokasi');

        return [
            'lokasiNama' => $kalab->lokasi?->nama_lokasi ?? 'Laboratorium belum ditautkan',
            'summary' => $this->buildKalabSummary($kalab),
            'dokumenSopTerbaru' => $this->buildKalabSopTerbaru(),
        ];
    }

    /**
     * @return array{checklist_aktif: int, dokumen_sop: int}
     */
    private function buildKalabSummary(User $kalab): array
    {
        $checklistAktif = 0;
        if ($kalab->lokasi_id !== null) {
            $checklistAktif = MasterChecklist::query()
                ->where('jenis_pengelola', 'Kalab')
                ->where('lokasi_id', $kalab->lokasi_id)
                ->where('status', 'Aktif')
                ->count();
        }

        return [
            'checklist_aktif' => $checklistAktif,
            'dokumen_sop' => SopDokumen::query()->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildKalabSopTerbaru(): array
    {
        return SopDokumen::query()
            ->orderByDesc('updated_at')
            ->limit(self::KALAB_RECENT_LIMIT)
            ->get(['id', 'judul', 'original_filename', 'updated_at'])
            ->map(fn (SopDokumen $row) => [
                'id' => $row->id,
                'judul' => $row->judul,
                'original_filename' => $row->original_filename,
                'updated_at' => $row->updated_at?->timezone(config('app.timezone'))->translatedFormat('d M Y') ?? '-',
                'preview_url' => route('sop.preview', $row, false),
            ])
            ->all();
    }

    /**
     * @return array{
     *   laporan_bulan_ini: int,
     *   menunggu: int,
     *   dalam_proses: int,
     *   selesai: int,
     * }
     */
    private function buildSatpamSummary(User $satpam, Carbon $today): array
    {
        $laporan = LaporanInsiden::query()
            ->where('satpam_id', $satpam->id)
            ->with('tindakLanjut')
            ->get();

        $laporanBulanIni = $laporan
            ->filter(fn (LaporanInsiden $row) => $row->tanggal_waktu
                && $row->tanggal_waktu->year === $today->year
                && $row->tanggal_waktu->month === $today->month)
            ->count();

        $menunggu = 0;
        $dalamProses = 0;
        $selesai = 0;

        foreach ($laporan as $row) {
            $status = $this->displayStatus(
                $row->tindakLanjut?->status_perbaikan,
                $row->tindakLanjut?->tanggal_tindakan,
            );

            match ($status) {
                'Selesai' => $selesai++,
                'Dalam Proses' => $dalamProses++,
                default => $menunggu++,
            };
        }

        return [
            'laporan_bulan_ini' => $laporanBulanIni,
            'menunggu' => $menunggu,
            'dalam_proses' => $dalamProses,
            'selesai' => $selesai,
        ];
    }

    /**
     * @return list<array{
     *   id: int,
     *   nomor: string,
     *   tanggal: string,
     *   jenis: string,
     *   lokasi: string,
     *   status: string,
     * }>
     */
    private function buildSatpamLaporanTerbaru(User $satpam, string $timezone): array
    {
        return LaporanInsiden::query()
            ->where('satpam_id', $satpam->id)
            ->with(['lokasi', 'tindakLanjut'])
            ->orderByDesc('tanggal_waktu')
            ->orderByDesc('id')
            ->limit(self::SATPAM_RECENT_LIMIT)
            ->get()
            ->map(function (LaporanInsiden $laporan) use ($timezone) {
                $lokasiLabel = $laporan->lokasi
                    ? $laporan->lokasi->nama_lokasi
                    : ($laporan->lokasi_manual ?? 'Lokasi tidak diketahui');

                $tl = $laporan->tindakLanjut;

                return [
                    'id' => $laporan->id,
                    'nomor' => 'INS-'.str_pad((string) $laporan->id, 5, '0', STR_PAD_LEFT),
                    'tanggal' => $laporan->tanggal_waktu
                        ? $laporan->tanggal_waktu->timezone($timezone)->translatedFormat('d M Y, H:i')
                        : '-',
                    'jenis' => $laporan->jenis_insiden,
                    'lokasi' => $lokasiLabel,
                    'status' => $this->displayStatus(
                        $tl?->status_perbaikan,
                        $tl?->tanggal_tindakan,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /**
     *  menghitung rata-rata skor risiko temuan per lokasi
     * @return array<string, mixed>
     */
    public function dataForExecutiveDashboard(?Carbon $today = null): array
    {
        $today ??= Carbon::today();
        $timezone = config('app.timezone');

        $summary = $this->buildSummary($today);
        $risikoChart = $this->buildRisikoPerLokasi();
        $risikoPerLokasi = $risikoChart['items'];
        $trenPerEmpatBulan = $this->buildTrenCaturwulan((int) $today->year);
        $temuanPrioritas = $this->buildTemuanPrioritas($timezone);
        $timeline = $this->buildTimelineTindakLanjut($timezone);

        return [
            'summary' => $summary,
            'trenPerEmpatBulan' => $trenPerEmpatBulan,
            'risikoPerLokasi' => $risikoPerLokasi,
            'totalSkorLokasi' => collect($risikoPerLokasi)->sum('skor'),
            'risikoMeta' => [
                'total_lokasi' => $risikoChart['total_lokasi'],
                'has_more' => $risikoChart['has_more'],
            ],
            'warnaDonat' => self::DONUT_COLORS,
            'prioritasTemuan' => $temuanPrioritas,
            'timeline' => $timeline,
            'maxTemuan' => max(collect($trenPerEmpatBulan)->max('temuan') ?? 0, 1),
        ];
    }

    /**
     * @return array<string, int|float|null|bool>
     */
    private function buildSummary(Carbon $today): array
    {
        $startBulanIni = $today->copy()->startOfMonth();
        $startBulanLalu = $today->copy()->subMonth()->startOfMonth();
        $endBulanLalu = $today->copy()->subMonth()->endOfMonth();

        $temuanBulanIni = $this->countTemuanBetween($startBulanIni, $today->copy()->endOfDay());
        $temuanBulanLalu = $this->countTemuanBetween($startBulanLalu, $endBulanLalu);

        $tindakLanjut = $this->tindakLanjutCounts();
        // Expired + mendekati expired (≤ 30 hari)
        $aparWarning = Apar::query()
            ->whereDate('tanggal_expired', '<=', $today->copy()->addDays(30))
            ->count();

        $persenPerubahan = null;
        if ($temuanBulanLalu > 0) {
            $persenPerubahan = round((($temuanBulanIni - $temuanBulanLalu) / $temuanBulanLalu) * 100);
        } elseif ($temuanBulanIni > 0) {
            $persenPerubahan = 100;
        }

        $totalTl = $tindakLanjut['total'];
        $selesaiTl = $tindakLanjut['selesai'];

        return [
            'temuan_bulan_ini' => $temuanBulanIni,
            'temuan_bulan_lalu' => $temuanBulanLalu,
            'persen_perubahan_temuan' => $persenPerubahan,
            'tindak_lanjut_selesai' => $selesaiTl,
            'tindak_lanjut_total' => $totalTl,
            'tindak_lanjut_persen' => $totalTl > 0 ? (int) round(($selesaiTl / $totalTl) * 100) : 0,
            'tindak_lanjut_belum_selesai' => max($totalTl - $selesaiTl, 0),
            'apar_mendekati_expired' => $aparWarning,
        ];
    }

    private function countTemuanBetween(Carbon $start, Carbon $end): int
    {
        return DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->whereHas('inspeksi', function ($query) use ($start, $end) {
                $query->whereBetween('tanggal_inspeksi', [$start, $end]);
            })
            ->count();
    }

    /**
     * @return array{total: int, selesai: int}
     */
    private function tindakLanjutCounts(): array
    {
        $inspeksiTotal = DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->count();

        $insidenTotal = LaporanInsiden::query()->count();
        $total = $inspeksiTotal + $insidenTotal;

        $inspeksiSelesai = TindakLanjutInspeksi::query()
            ->where('status_perbaikan', 'Selesai')
            ->whereHas('detailInspeksi', fn ($q) => $q->where('status', DetailInspeksi::STATUS_TIDAK))
            ->count();

        $insidenSelesai = TindakLanjutInsiden::query()
            ->where('status_perbaikan', 'Selesai')
            ->count();

        return [
            'total' => $total,
            'selesai' => $inspeksiSelesai + $insidenSelesai,
        ];
    }

    /**
     * @return array{
     *   items: list<array{lokasi: string, skor: int, is_lainnya?: bool}>,
     *   total_lokasi: int,
     *   has_more: bool,
     * }
     */
    private function buildRisikoPerLokasi(): array
    {
        $all = DetailInspeksi::query()
            ->select([
                'lokasi.nama_lokasi',
                DB::raw('ROUND(AVG(detail_inspeksi.skor_risiko_hasil)) as skor_rata'),
            ])
            ->join('inspeksi_k3l', 'detail_inspeksi.inspeksi_k3l_id', '=', 'inspeksi_k3l.id')
            ->join('lokasi', 'inspeksi_k3l.lokasi_id', '=', 'lokasi.id')
            ->where('detail_inspeksi.status', DetailInspeksi::STATUS_TIDAK)
            ->whereNotNull('detail_inspeksi.skor_risiko_hasil')
            ->groupBy('lokasi.id', 'lokasi.nama_lokasi')
            ->orderByDesc('skor_rata')
            ->get()
            ->map(fn ($row) => [
                'lokasi' => (string) $row->nama_lokasi,
                'skor' => (int) $row->skor_rata,
            ])
            ->values();

        $totalLokasi = $all->count();

        if ($totalLokasi <= self::RISIKO_LOKASI_TOP) {
            return [
                'items' => $all->all(),
                'total_lokasi' => $totalLokasi,
                'has_more' => false,
            ];
        }

        $top = $all->take(self::RISIKO_LOKASI_TOP);
        $rest = $all->slice(self::RISIKO_LOKASI_TOP);

        $items = $top->values()->all();
        $items[] = [
            'lokasi' => 'Lainnya ('.$rest->count().' lokasi)',
            'skor' => (int) $rest->sum('skor'),
            'is_lainnya' => true,
        ];

        return [
            'items' => $items,
            'total_lokasi' => $totalLokasi,
            'has_more' => true,
        ];
    }

    /**
     * @return list<array{periode: string, temuan: int}>
     */
    private function buildTrenCaturwulan(int $tahun): array
    {
        $temuanPerBulan = [];

        DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->whereHas('inspeksi', fn ($query) => $query->whereYear('tanggal_inspeksi', $tahun))
            ->with('inspeksi:id,tanggal_inspeksi')
            ->get(['id', 'inspeksi_k3l_id'])
            ->each(function (DetailInspeksi $detail) use (&$temuanPerBulan) {
                $bulan = (int) ($detail->inspeksi?->tanggal_inspeksi?->month ?? 0);
                if ($bulan < 1) {
                    return;
                }

                $temuanPerBulan[$bulan] = ($temuanPerBulan[$bulan] ?? 0) + 1;
            });

        $result = [];

        foreach (Caturwulan::caturwulanNumbers() as $caturwulan) {
            $total = 0;
            foreach (Caturwulan::bulanNumbersForCaturwulan($caturwulan) as $bulan) {
                $total += (int) ($temuanPerBulan[$bulan] ?? 0);
            }

            $result[] = [
                'periode' => Caturwulan::shortLabel($caturwulan),
                'rentang_bulan' => Caturwulan::rentangBulan($caturwulan),
                'tahun' => $tahun,
                'temuan' => $total,
            ];
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTemuanPrioritas(string $timezone): array
    {
        return DetailInspeksi::query()
            ->where('status', DetailInspeksi::STATUS_TIDAK)
            ->whereIn('level_risiko_hasil', ['Tinggi', 'Sangat Tinggi'])
            ->with([
                'inspeksi.lokasi',
                'inspeksi.masterChecklist',
                'itemChecklist',
                'tindakLanjut',
            ])
            ->orderByDesc('skor_risiko_hasil')
            ->orderByDesc('created_at')
            ->limit(self::PRIORITAS_LIMIT)
            ->get()
            ->map(function (DetailInspeksi $detail) use ($timezone) {
                $tanggal = $detail->inspeksi?->tanggal_inspeksi
                  ?? $detail->created_at;

                $tl = $detail->tindakLanjut;

                return [
                    'id' => $detail->id,
                    'tanggal' => $tanggal
                      ? $tanggal->timezone($timezone)->translatedFormat('d M Y')
                      : '-',
                    'lokasi' => $detail->inspeksi?->lokasi?->nama_lokasi ?? '-',
                    'kategori' => $detail->inspeksi?->masterChecklist?->nama_checklist ?? '-',
                    'deskripsi' => $this->deskripsiTemuan($detail),
                    'level' => $detail->level_risiko_hasil ?? 'Rendah',
                    'skor' => (int) ($detail->skor_risiko_hasil ?? 0),
                    'status' => $this->displayStatus(
                        $tl?->status_perbaikan,
                        $tl?->tanggal_tindakan,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    private function deskripsiTemuan(DetailInspeksi $detail): string
    {
        $analisa = trim((string) ($detail->analisa_risiko ?? ''));
        if ($analisa !== '') {
            return $analisa;
        }

        $rekomendasi = trim((string) ($detail->rekomendasi ?? ''));
        if ($rekomendasi !== '') {
            return $rekomendasi;
        }

        return $detail->itemChecklist?->nama_item ?? '-';
    }

    /**
     * @return list<array{date: string, status: string, desc: string, pelaksana: string}>
     */
    private function buildTimelineTindakLanjut(string $timezone): array
    {
        $inspeksiTimeline = TindakLanjutInspeksi::query()
            ->with(['detailInspeksi.itemChecklist', 'petugas'])
            ->whereHas('detailInspeksi', fn ($q) => $q->where('status', DetailInspeksi::STATUS_TIDAK))
            ->orderByDesc('updated_at')
            ->limit(self::TIMELINE_LIMIT)
            ->get()
            ->map(fn (TindakLanjutInspeksi $tl) => $this->serializeTimelineInspeksi($tl, $timezone));

        $insidenTimeline = TindakLanjutInsiden::query()
            ->with(['laporanInsiden', 'petugas'])
            ->orderByDesc('updated_at')
            ->limit(self::TIMELINE_LIMIT)
            ->get()
            ->map(fn (TindakLanjutInsiden $tl) => $this->serializeTimelineInsiden($tl, $timezone));

        return $inspeksiTimeline
            ->toBase()
            ->merge($insidenTimeline->toBase())
            ->sortByDesc('sort_at')
            ->take(self::TIMELINE_LIMIT)
            ->map(fn (array $row) => [
                'date' => $row['date'],
                'status' => $row['status'],
                'desc' => $row['desc'],
                'pelaksana' => $row['pelaksana'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{date: string, status: string, desc: string, pelaksana: string, sort_at: string}
     */
    private function serializeTimelineInspeksi(TindakLanjutInspeksi $tl, string $timezone): array
    {
        $detail = $tl->detailInspeksi;
        $desc = $detail?->itemChecklist?->nama_item ?? '-';

        $timestamp = $tl->updated_at ?? $tl->created_at;

        return [
            'date' => $timestamp
              ? $timestamp->timezone($timezone)->translatedFormat('d F Y, H:i')
              : '-',
            'status' => $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan),
            'desc' => $desc,
            'pelaksana' => $tl->petugas?->nama_lengkap ?? 'Belum ditugaskan',
            'sort_at' => $timestamp?->toIso8601String() ?? '',
        ];
    }

    /**
     * @return array{date: string, status: string, desc: string, pelaksana: string, sort_at: string}
     */
    private function serializeTimelineInsiden(TindakLanjutInsiden $tl, string $timezone): array
    {
        $laporan = $tl->laporanInsiden;
        $desc = $laporan?->jenis_insiden ?? '-';

        $timestamp = $tl->updated_at ?? $tl->created_at;

        return [
            'date' => $timestamp
              ? $timestamp->timezone($timezone)->translatedFormat('d F Y, H:i')
              : '-',
            'status' => $this->displayStatus($tl->status_perbaikan, $tl->tanggal_tindakan),
            'desc' => $desc,
            'pelaksana' => $tl->petugas?->nama_lengkap ?? 'Belum ditugaskan',
            'sort_at' => $timestamp?->toIso8601String() ?? '',
        ];
    }

    private function displayStatus(?string $statusPerbaikan, ?Carbon $tanggalTindakan): string
    {
        if ($statusPerbaikan === 'Selesai') {
            return 'Selesai';
        }

        return $tanggalTindakan ? 'Dalam Proses' : 'Menunggu Tindakan';
    }
}
