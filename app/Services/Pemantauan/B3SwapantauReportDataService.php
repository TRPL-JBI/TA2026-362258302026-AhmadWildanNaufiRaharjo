<?php

namespace App\Services\Pemantauan;

use App\Models\JenisLimbahB3;
use App\Models\LaporanLimbahB3;
use App\Models\LogbookLimbahB3;
use App\Models\ManifestLimbahB3;
use App\Support\B3Semester;

class B3SwapantauReportDataService
{
    /**
     * @return array<string, mixed>
     */
    public function detailForReport(LaporanLimbahB3 $laporan): array
    {
        $laporan->load(['jenisLimbah', 'logbook', 'manifest', 'petugas:id,nama_lengkap']);

        $bulanNames = B3Semester::semesterToBulanMap()[$laporan->semester]
            ?? B3Semester::semesterToBulanMap()[1];

        $logbookByBulan = $laporan->logbook
            ->sortBy('tanggal_masuk')
            ->groupBy('bulan');

        $logbookBulanList = [];

        foreach ($bulanNames as $nama) {
            $bulanNumber = B3Semester::bulanNumberFromName($nama);
            $entries = $logbookByBulan->get($bulanNumber, collect());

            $logbookBulanList[] = [
                'nama' => $nama,
                'entries' => $entries->map(fn (LogbookLimbahB3 $row) => [
                    'tanggal_masuk' => $row->tanggal_masuk?->format('j-n-Y') ?? '',
                    'tanggal_keluar' => $row->tanggal_keluar?->format('j-n-Y') ?? '',
                    'jenis_limbah' => $row->jenis_limbah,
                    'sumber_limbah' => $row->sumber_limbah,
                    'jumlah_masuk_kg' => $this->formatDecimal($row->jumlah_masuk_kg),
                    'jumlah_keluar_kg' => $row->jumlah_keluar_kg !== null
                        ? $this->formatDecimal($row->jumlah_keluar_kg)
                        : '',
                    'pengemasan' => $row->pengemasan ?? '',
                ])->values()->all(),
            ];
        }

        return [
            'semester' => $laporan->semester,
            'tahun' => $laporan->tahun,
            'periode_label' => B3Semester::labelWithYear($laporan->semester, $laporan->tahun),
            'periode_rentang' => B3Semester::periodeRentang($laporan->semester),
            'petugas' => $laporan->petugas?->nama_lengkap ?? '-',
            'jenis_list' => $laporan->jenisLimbah->map(fn (JenisLimbahB3 $row) => [
                'nama_limbah' => $row->nama_limbah,
                'kode_limbah' => $row->kode_limbah,
                'sumber_limbah' => $row->sumber_limbah,
                'karakteristik' => $row->karakteristik,
                'pengemasan' => $row->pengemasan,
                'masa_simpan_hari' => (string) $row->masa_simpan_hari,
            ])->values()->all(),
            'logbook_bulan_list' => $logbookBulanList,
            'manifest_list' => $laporan->manifest
                ->sortBy('tanggal_manifest')
                ->map(fn (ManifestLimbahB3 $row) => $this->serializeManifest($row))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeManifest(ManifestLimbahB3 $row): array
    {
        return [
            'nomor_manifest' => $row->nomor_manifest,
            'nama_pengirim' => $row->nama_pengirim,
            'alamat_pengirim' => $row->alamat_pengirim,
            'pengirim_nama_alamat' => $this->combineLines($row->nama_pengirim, $row->alamat_pengirim),
            'nama_fasilitas_penyimpanan' => $row->nama_fasilitas_penyimpanan ?? '',
            'penanggung_jawab_pengirim' => $row->penanggung_jawab_pengirim ?? '',
            'jabatan_pj_pengirim' => $row->jabatan_pj_pengirim ?? '',
            'kode_limbah' => $row->kode_limbah,
            'nama_limbah' => $row->nama_limbah,
            'nama_teknik' => $row->nama_teknik ?? '',
            'periode_limbah' => $this->formatPeriodeRange(
                $row->periode_limbah_mulai?->format('Y-m-d'),
                $row->periode_limbah_selesai?->format('Y-m-d'),
            ),
            'karakteristik_limbah' => $row->karakteristik_limbah,
            'jenis_kemasan' => $row->jenis_kemasan,
            'jumlah_kemasan' => (string) $row->jumlah_kemasan,
            'jumlah_limbah_ton' => $this->formatDecimal($row->jumlah_limbah_ton),
            'keterangan_tambahan' => $row->keterangan_tambahan ?? '',
            'tujuan_pengangkutan' => $row->tujuan_pengangkutan,
            'nama_pengangkut' => $row->nama_pengangkut,
            'alamat_pengangkut' => $row->alamat_pengangkut,
            'pengangkut_nama_alamat' => $this->combineLines($row->nama_pengangkut, $row->alamat_pengangkut),
            'no_telepon_darurat' => $row->no_telepon_darurat ?? '',
            'jumlah_ril' => $row->jumlah_ril !== null ? (string) $row->jumlah_ril : '',
            'identitas_alat_angkut' => $row->identitas_alat_angkut ?? '',
            'waktu_mulai_pengangkutan' => $row->waktu_mulai_pengangkutan?->format('Y-m-d H:i:s') ?? '',
            'waktu_selesai_pengangkutan' => $row->waktu_selesai_pengangkutan?->format('Y-m-d H:i:s') ?? '',
            'penanggung_jawab_pengangkut' => $row->penanggung_jawab_pengangkut ?? '',
            'jabatan_pj_pengangkut' => $row->jabatan_pj_pengangkut ?? '',
            'nama_penerima' => $row->nama_penerima,
            'alamat_penerima' => $row->alamat_penerima,
            'penerima_nama_alamat' => $this->combineLines($row->nama_penerima, $row->alamat_penerima),
            'no_telepon_penerima' => $row->no_telepon_penerima ?? '',
            'jenis_pengelolaan' => $row->jenis_pengelolaan,
            'jumlah_diterima_kg' => $row->jumlah_diterima_kg !== null
                ? $this->formatDecimal($row->jumlah_diterima_kg)
                : '',
            'penanggung_jawab_penerima' => $row->penanggung_jawab_penerima ?? '',
            'jabatan_pj_penerima' => $row->jabatan_pj_penerima ?? '',
        ];
    }

    private function combineLines(string $first, string $second): string
    {
        return trim($first."\n".$second);
    }

    private function formatPeriodeRange(?string $mulai, ?string $selesai): string
    {
        if (($mulai ?? '') === '' && ($selesai ?? '') === '') {
            return '';
        }

        if ($mulai !== null && $mulai !== '' && $selesai !== null && $selesai !== '') {
            return $mulai.' s/d '.$selesai;
        }

        return $mulai ?: ($selesai ?? '');
    }

    private function formatDecimal(float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }
}
