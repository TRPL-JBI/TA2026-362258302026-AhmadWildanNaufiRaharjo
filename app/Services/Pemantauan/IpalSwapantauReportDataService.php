<?php

namespace App\Services\Pemantauan;

use App\Models\DetailIpalHarian;
use App\Models\LaporanIpal;
use App\Support\IpalTriwulan;

class IpalSwapantauReportDataService
{
    /**
     * @return array<string, mixed>
     */
    public function detailForReport(LaporanIpal $laporan): array
    {
        $laporan->load(['detailHarian', 'dampakLingkungan', 'petugas:id,nama_lengkap']);

        $triwulanKey = IpalTriwulan::keyFromNumber($laporan->triwulan);
        $bulanNames = IpalTriwulan::triwulanToBulanMap()[$triwulanKey] ?? [];

        $detailsByBulan = $laporan->detailHarian
            ->sortBy('tanggal_sampling')
            ->groupBy('bulan');

        $bulanList = [];

        foreach ($bulanNames as $nama) {
            $bulanNumber = IpalTriwulan::bulanNumberFromName($nama);
            $entries = $detailsByBulan->get($bulanNumber, collect());
            $entriesByDay = [];

            foreach ($entries as $row) {
                if ($row->tanggal_sampling === null) {
                    continue;
                }

                $day = (int) $row->tanggal_sampling->format('j');
                $entriesByDay[$day] = $this->serializeDailyEntry($row, $nama, $laporan->tahun);
            }

            $bulanList[] = [
                'nama' => $nama,
                'bulan_number' => $bulanNumber,
                'entries_by_day' => $entriesByDay,
            ];
        }

        $dampak = $laporan->dampakLingkungan;

        return [
            'triwulan' => $laporan->triwulan,
            'tahun' => $laporan->tahun,
            'periode_label' => IpalTriwulan::label($laporan->triwulan, $laporan->tahun),
            'triwulan_key' => $triwulanKey,
            'petugas' => $laporan->petugas?->nama_lengkap ?? '-',
            'evaluasi_kinerja' => $laporan->evaluasi_kinerja ?? '',
            'bulan_list' => $bulanList,
            'dampak' => $dampak === null ? null : [
                'jenis_dampak' => $dampak->jenis_dampak,
                'sumber_dampak' => $dampak->sumber_dampak,
                'parameter_pemantauan' => $dampak->parameter_pemantauan,
                'tolak_ukur' => $dampak->tolak_ukur,
                'lokasi_pengelolaan' => $dampak->lokasi_pengelolaan ?? '',
                'evaluasi_hasil' => $dampak->evaluasi_hasil ?? $laporan->evaluasi_kinerja ?? '',
                'tindakan_perbaikan' => $dampak->tindakan_perbaikan ?? '',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function serializeDailyEntry(DetailIpalHarian $row, string $bulanNama, int $tahun): array
    {
        return [
            'tanggal_label' => sprintf(
                '%s %s %d',
                $row->tanggal_sampling?->format('d') ?? '',
                $bulanNama,
                $tahun,
            ),
            'debit_input' => $this->formatDecimal($row->debit_input_m3),
            'debit_output' => $this->formatDecimal($row->debit_output_m3),
            'ph' => $row->ph !== null ? $this->formatDecimal($row->ph) : '',
            'suhu' => $row->suhu_celcius !== null ? $this->formatDecimal($row->suhu_celcius) : '',
        ];
    }

    private function formatDecimal(float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numeric = (float) $value;

        return rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');
    }
}
