<?php

namespace App\Services\Pemantauan;

use App\Models\DetailIpamMingguan;
use App\Models\LaporanIpam;
use App\Models\TitikIpam;
use App\Models\UnitIpam;
use App\Models\User;
use App\Support\IpamBulan;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IpamRekapReportDataService
{
    /**
     * @return array<string, mixed>
     */
    public function detailForReport(int $tahun, int $bulan): array
    {
        $laporan = LaporanIpam::query()
            ->with(['titikIpam.unitIpam', 'detailMingguan', 'petugas:id,nama_lengkap'])
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        if ($laporan->isEmpty()) {
            throw ValidationException::withMessages([
                'periode' => 'Data laporan IPAM untuk periode ini tidak ditemukan.',
            ]);
        }

        $bulanName = IpamBulan::bulanNameFromNumber($bulan);
        $first = $laporan->first();
        $firstDetail = $laporan
            ->flatMap(fn (LaporanIpam $row) => $row->detailMingguan)
            ->sortBy('minggu_ke')
            ->first();

        $units = UnitIpam::query()
            ->with(['titikIpam' => fn ($query) => $query->orderBy('titik_lokasi')])
            ->whereIn(
                'id',
                $laporan->map(fn (LaporanIpam $row) => $row->titikIpam?->unit_ipam_id)->filter()->unique(),
            )
            ->orderBy('nama_unit')
            ->get();

        $unitPayloads = [];
        $allPh = collect();
        $allAlt = collect();
        $hasSalmonellaPositif = false;

        foreach ($units as $unit) {
            $titikLetters = $this->titikLetters($unit->titikIpam);
            $unitLaporan = $laporan->filter(
                fn (LaporanIpam $row) => $row->titikIpam?->unit_ipam_id === $unit->id,
            );

            $weeks = $unitLaporan
                ->flatMap(fn (LaporanIpam $row) => $row->detailMingguan)
                ->pluck('minggu_ke')
                ->unique()
                ->sort()
                ->values();

            $weekPayloads = [];

            foreach ($weeks as $mingguKe) {
                $titikRows = [];
                $hasData = false;

                foreach ($unit->titikIpam as $titik) {
                    $row = $unitLaporan->firstWhere('titik_ipam_id', $titik->id);
                    $detail = $row?->detailMingguan->firstWhere('minggu_ke', $mingguKe);

                    if ($detail === null) {
                        $titikRows[] = [
                            'kode' => $titikLetters[$titik->id] ?? '-',
                            'lokasi' => $titik->titik_lokasi,
                            'ph' => '-',
                            'alt' => '-',
                            'salmonella' => '-',
                            'salmonella_display' => '-',
                            'status' => '-',
                        ];

                        continue;
                    }

                    $hasData = true;
                    $titikRow = $this->serializeTitikRow($titik, $titikLetters, $detail, $allPh, $allAlt);
                    if ($titikRow['salmonella'] === 'Positif') {
                        $hasSalmonellaPositif = true;
                    }
                    $titikRows[] = $titikRow;
                }

                if ($hasData && $titikRows !== []) {
                    $weekPayloads[] = [
                        'minggu_ke' => (int) $mingguKe,
                        'titik' => $titikRows,
                    ];
                }
            }

            $unitPayloads[] = [
                'nama_unit' => $unit->nama_unit,
                'weeks' => $weekPayloads,
                'rekap' => $this->buildUnitRekap($unit, $unitLaporan, $weeks),
            ];
        }

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'bulan_label' => $bulanName,
            'periode_label' => sprintf('%s %d', $bulanName, $tahun),
            'petugas' => $first?->petugas?->nama_lengkap ?? '-',
            'units' => $unitPayloads,
            'parameter' => [
                'ph' => $this->formatPhRange($allPh),
                'salmonella' => $hasSalmonellaPositif ? 'Positif' : 'Negatif',
                'alt' => $this->formatAltRange($allAlt),
            ],
            'notes' => [
                'kendala' => $firstDetail?->kendala ?? '',
                'rekomendasi' => $firstDetail?->rekomendasi ?? '',
                'kesimpulan' => $first?->kesimpulan ?? '',
            ],
        ];
    }

    public function petugasForPeriode(int $tahun, int $bulan): ?User
    {
        $petugasId = LaporanIpam::query()
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->value('petugas_id');

        if ($petugasId === null) {
            return null;
        }

        return User::query()->find($petugasId);
    }

    /**
     * @param  Collection<int, TitikIpam>  $titikList
     * @return array<int, string>
     */
    private function titikLetters(Collection $titikList): array
    {
        $letters = [];

        foreach ($titikList->values() as $index => $titik) {
            $letters[$titik->id] = chr(ord('A') + $index);
        }

        return $letters;
    }

    /**
     * @param  array<int, string>  $titikLetters
     * @param  Collection<float>  $allPh
     * @param  Collection<string>  $allAlt
     */
    private function serializeTitikRow(
        TitikIpam $titik,
        array $titikLetters,
        DetailIpamMingguan $detail,
        Collection $allPh,
        Collection $allAlt,
    ): array {
        if ($detail->ph !== null) {
            $allPh->push((float) $detail->ph);
        }

        $alt = trim((string) ($detail->alt_cfu_ml ?? ''));
        if ($alt !== '' && $alt !== '-') {
            $allAlt->push($alt);
        }

        return [
            'kode' => $titikLetters[$titik->id] ?? '-',
            'lokasi' => $titik->titik_lokasi,
            'ph' => $this->formatPh($detail->ph),
            'alt' => $this->formatAlt($detail->alt_cfu_ml),
            'salmonella' => (string) ($detail->salmonella ?? '-'),
            'salmonella_display' => $this->formatSalmonella($detail->salmonella),
            'status' => (string) ($detail->status ?? '-'),
        ];
    }

    /**
     * @param  Collection<int, LaporanIpam>  $unitLaporan
     * @param  Collection<int, int>  $weeks
     * @return array<string, mixed>
     */
    private function buildUnitRekap(UnitIpam $unit, Collection $unitLaporan, Collection $weeks): array
    {
        $titikIds = $unit->titikIpam->pluck('id');
        $sampledTitikIds = $unitLaporan->pluck('titik_ipam_id')->unique();

        $tidakBaikTitik = $unitLaporan
            ->filter(function (LaporanIpam $row) {
                return $row->detailMingguan->contains(
                    fn (DetailIpamMingguan $detail) => $detail->status === 'Tidak Baik',
                );
            })
            ->pluck('titik_ipam_id')
            ->unique();

        $baikTitik = $sampledTitikIds->diff($tidakBaikTitik);

        return [
            'minggu_sampling' => $this->formatMingguSampling($weeks->all()),
            'jumlah_titik' => $titikIds->intersect($sampledTitikIds)->count(),
            'hasil_baik' => $baikTitik->count(),
            'hasil_tidak_baik' => $tidakBaikTitik->count(),
            'catatan' => $tidakBaikTitik->isEmpty()
                ? 'Semua sesuai baku mutu'
                : 'Terdapat titik dengan hasil tidak baik',
        ];
    }

    /**
     * @param  list<int>  $weeks
     */
    private function formatMingguSampling(array $weeks): string
    {
        if ($weeks === []) {
            return '-';
        }

        $labels = array_map(fn (int $week) => 'Minggu '.$week, $weeks);

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels).' & '.$last;
    }

    private function formatPh(?float $ph): string
    {
        if ($ph === null) {
            return '-';
        }

        return number_format($ph, 2, '.', '');
    }

    private function formatAlt(?string $alt): string
    {
        $value = trim((string) $alt);

        return $value === '' ? '-' : $value;
    }

    private function formatSalmonella(?string $salmonella): string
    {
        if ($salmonella === null || $salmonella === '' || $salmonella === 'Negatif') {
            return '-';
        }

        return $salmonella;
    }

    /**
     * @param  Collection<float>  $values
     */
    private function formatPhRange(Collection $values): string
    {
        if ($values->isEmpty()) {
            return '-';
        }

        $min = $values->min();
        $max = $values->max();

        return $this->formatDecimalComma((float) $min).' – '.$this->formatDecimalComma((float) $max);
    }

    /**
     * @param  Collection<string>  $values
     */
    private function formatAltRange(Collection $values): string
    {
        if ($values->isEmpty()) {
            return '-';
        }

        $unique = $values->unique()->values();

        if ($unique->count() === 1) {
            return $unique->first().' cfu/ml';
        }

        return $unique->first().' - '.$unique->last().' cfu/ml';
    }

    private function formatDecimalComma(float $value): string
    {
        return str_replace('.', ',', number_format($value, 2, '.', ''));
    }
}
