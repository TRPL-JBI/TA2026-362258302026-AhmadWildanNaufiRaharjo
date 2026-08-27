<?php

namespace App\Http\Requests\Pemantauan;

use App\Models\ManifestLimbahB3;
use App\Support\B3Semester;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaporanB3Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Petugas K3LH') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allBulan = array_unique(array_merge(...array_values(B3Semester::semesterToBulanMap())));

        return [
            'semester' => ['required', 'integer', Rule::in(B3Semester::semesterOptions())],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'jenis_list' => ['nullable', 'array'],
            'jenis_list.*.nama_limbah' => ['nullable', 'string', 'max:200'],
            'jenis_list.*.kode_limbah' => ['nullable', 'string', 'max:50'],
            'jenis_list.*.sumber_limbah' => ['nullable', 'string', 'max:200'],
            'jenis_list.*.karakteristik' => ['nullable', 'string', 'max:100'],
            'jenis_list.*.pengemasan' => ['nullable', 'string', 'max:100'],
            'jenis_list.*.masa_simpan_hari' => ['nullable', 'integer', 'min:1'],
            'logbook_bulan_list' => ['required', 'array', 'min:1'],
            'logbook_bulan_list.*.nama' => ['required', 'string', Rule::in($allBulan)],
            'logbook_bulan_list.*.entries' => ['array'],
            'logbook_bulan_list.*.entries.*.tanggal_masuk' => ['nullable', 'date'],
            'logbook_bulan_list.*.entries.*.tanggal_keluar' => ['nullable', 'date'],
            'logbook_bulan_list.*.entries.*.jenis_limbah' => ['nullable', 'string', 'max:200'],
            'logbook_bulan_list.*.entries.*.sumber_limbah' => ['nullable', 'string', 'max:200'],
            'logbook_bulan_list.*.entries.*.jumlah_masuk_kg' => ['nullable', 'numeric', 'min:0'],
            'logbook_bulan_list.*.entries.*.jumlah_keluar_kg' => ['nullable', 'numeric', 'min:0'],
            'logbook_bulan_list.*.entries.*.pengemasan' => ['nullable', 'string', 'max:100'],
            'manifest_list' => ['nullable', 'array'],
            'manifest_list.*.nomor_manifest' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.tanggal_manifest' => ['nullable', 'date'],
            'manifest_list.*.nama_pengirim' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.alamat_pengirim' => ['nullable', 'string'],
            'manifest_list.*.nama_fasilitas_penyimpanan' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.penanggung_jawab_pengirim' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.jabatan_pj_pengirim' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.kode_limbah' => ['nullable', 'string', 'max:50'],
            'manifest_list.*.nama_limbah' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.nama_teknik' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.periode_limbah_mulai' => ['nullable', 'date'],
            'manifest_list.*.periode_limbah_selesai' => ['nullable', 'date'],
            'manifest_list.*.karakteristik_limbah' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.jenis_kemasan' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.jumlah_kemasan' => ['nullable', 'integer', 'min:1'],
            'manifest_list.*.jumlah_limbah_ton' => ['nullable', 'numeric', 'min:0'],
            'manifest_list.*.keterangan_tambahan' => ['nullable', 'string'],
            'manifest_list.*.tujuan_pengangkutan' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.nama_pengangkut' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.alamat_pengangkut' => ['nullable', 'string'],
            'manifest_list.*.no_telepon_darurat' => ['nullable', 'string', 'max:20'],
            'manifest_list.*.jumlah_ril' => ['nullable', 'integer', 'min:0'],
            'manifest_list.*.identitas_alat_angkut' => ['nullable', 'string', 'max:50'],
            'manifest_list.*.waktu_mulai_pengangkutan' => ['nullable', 'date'],
            'manifest_list.*.waktu_selesai_pengangkutan' => ['nullable', 'date'],
            'manifest_list.*.penanggung_jawab_pengangkut' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.jabatan_pj_pengangkut' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.nama_penerima' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.alamat_penerima' => ['nullable', 'string'],
            'manifest_list.*.no_telepon_penerima' => ['nullable', 'string', 'max:20'],
            'manifest_list.*.jenis_pengelolaan' => ['nullable', 'string', 'max:200'],
            'manifest_list.*.jumlah_diterima_kg' => ['nullable', 'numeric', 'min:0'],
            'manifest_list.*.penanggung_jawab_penerima' => ['nullable', 'string', 'max:100'],
            'manifest_list.*.jabatan_pj_penerima' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'semester.required' => 'Pilih semester laporan.',
            'semester.in' => 'Semester tidak valid.',
            'tahun.required' => 'Tahun wajib dipilih.',
            'logbook_bulan_list.required' => 'Data logbook wajib diisi.',
            'manifest_list.*.nomor_manifest.max' => 'Nomor manifest maksimal 100 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'logbook_bulan_list' => $this->normalizeNumericList($this->input('logbook_bulan_list', []), [
                'jumlah_masuk_kg',
                'jumlah_keluar_kg',
            ]),
            'manifest_list' => $this->normalizeNumericList($this->input('manifest_list', []), [
                'jumlah_limbah_ton',
                'jumlah_diterima_kg',
            ]),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateFilledJenisEntries($validator);
            $this->validateFilledLogbookEntries($validator);
            $this->validateFilledManifestEntries($validator);
            $this->validateManifestNumbers($validator);
        });
    }

    protected function validateFilledJenisEntries(Validator $validator): void
    {
        $jenisList = $this->input('jenis_list', []);

        if (! is_array($jenisList)) {
            return;
        }

        $requiredFields = [
            'nama_limbah' => 'Nama limbah wajib diisi.',
            'kode_limbah' => 'Kode limbah wajib diisi.',
            'sumber_limbah' => 'Sumber limbah wajib diisi.',
            'karakteristik' => 'Karakteristik wajib diisi.',
            'pengemasan' => 'Pengemasan wajib diisi.',
            'masa_simpan_hari' => 'Masa simpan wajib diisi.',
        ];

        foreach ($jenisList as $index => $jenis) {
            if (! is_array($jenis)) {
                continue;
            }

            foreach ($requiredFields as $field => $message) {
                if (trim((string) ($jenis[$field] ?? '')) === '') {
                    $validator->errors()->add("jenis_list.{$index}.{$field}", $message);
                }
            }
        }
    }

    protected function validateFilledLogbookEntries(Validator $validator): void
    {
        $bulanList = $this->input('logbook_bulan_list', []);
        $filledCount = 0;

        foreach ($bulanList as $bulanIndex => $bulan) {
            foreach ($bulan['entries'] ?? [] as $entryIndex => $entry) {
                if (! $this->isLogbookEntryTouched($entry)) {
                    continue;
                }

                $filledCount++;

                foreach (['tanggal_masuk', 'jenis_limbah', 'sumber_limbah', 'jumlah_masuk_kg'] as $field) {
                    if (trim((string) ($entry[$field] ?? '')) === '') {
                        $validator->errors()->add(
                            "logbook_bulan_list.{$bulanIndex}.entries.{$entryIndex}.{$field}",
                            'Field logbook wajib dilengkapi untuk entri yang diisi.',
                        );
                    }
                }
            }
        }

        if ($filledCount === 0) {
            $validator->errors()->add('logbook_bulan_list', 'Minimal satu entri logbook wajib diisi.');
        }
    }

    protected function validateFilledManifestEntries(Validator $validator): void
    {
        $manifestList = $this->input('manifest_list', []);

        if (! is_array($manifestList)) {
            return;
        }

        $requiredFields = [
            'nomor_manifest' => 'Nomor manifest wajib diisi.',
            'tanggal_manifest' => 'Tanggal manifest wajib diisi.',
            'nama_pengirim' => 'Nama pengirim wajib diisi.',
            'alamat_pengirim' => 'Alamat pengirim wajib diisi.',
            'kode_limbah' => 'Kode limbah manifest wajib diisi.',
            'nama_limbah' => 'Nama limbah manifest wajib diisi.',
            'karakteristik_limbah' => 'Karakteristik limbah manifest wajib diisi.',
            'jenis_kemasan' => 'Jenis kemasan wajib diisi.',
            'jumlah_kemasan' => 'Jumlah kemasan wajib diisi.',
            'jumlah_limbah_ton' => 'Jumlah limbah (ton) wajib diisi.',
            'tujuan_pengangkutan' => 'Tujuan pengangkutan wajib diisi.',
            'nama_pengangkut' => 'Nama pengangkut wajib diisi.',
            'alamat_pengangkut' => 'Alamat pengangkut wajib diisi.',
            'nama_penerima' => 'Nama penerima wajib diisi.',
            'alamat_penerima' => 'Alamat penerima wajib diisi.',
            'jenis_pengelolaan' => 'Jenis pengelolaan wajib diisi.',
        ];

        foreach ($manifestList as $index => $manifest) {
            if (! is_array($manifest) || ! $this->isManifestEntryTouched($manifest)) {
                continue;
            }

            foreach ($requiredFields as $field => $message) {
                if (trim((string) ($manifest[$field] ?? '')) === '') {
                    $validator->errors()->add("manifest_list.{$index}.{$field}", $message);
                }
            }
        }
    }

    protected function validateManifestNumbers(Validator $validator): void
    {
        $manifestList = $this->input('manifest_list', []);
        $seen = [];
        $ignoreLaporanId = $this->resolveLaporanIdForUniqueCheck();

        foreach ($manifestList as $index => $manifest) {
            if (! is_array($manifest) || ! $this->isManifestEntryTouched($manifest)) {
                continue;
            }

            $nomor = trim((string) ($manifest['nomor_manifest'] ?? ''));

            if ($nomor === '') {
                continue;
            }

            if (isset($seen[$nomor])) {
                $validator->errors()->add(
                    "manifest_list.{$index}.nomor_manifest",
                    'Nomor manifest duplikat dalam formulir.',
                );

                continue;
            }

            $seen[$nomor] = true;

            $exists = ManifestLimbahB3::query()
                ->where('nomor_manifest', $nomor)
                ->when($ignoreLaporanId !== null, fn ($query) => $query->where('laporan_limbah_b3_id', '!=', $ignoreLaporanId))
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    "manifest_list.{$index}.nomor_manifest",
                    'Nomor manifest sudah digunakan.',
                );
            }
        }
    }

    protected function resolveLaporanIdForUniqueCheck(): ?int
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function isJenisEntryTouched(array $entry): bool
    {
        foreach (['nama_limbah', 'kode_limbah', 'sumber_limbah', 'karakteristik', 'pengemasan', 'masa_simpan_hari'] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function isLogbookEntryTouched(array $entry): bool
    {
        foreach (['tanggal_masuk', 'tanggal_keluar', 'jenis_limbah', 'sumber_limbah', 'jumlah_masuk_kg', 'jumlah_keluar_kg', 'pengemasan'] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function isManifestEntryTouched(array $entry): bool
    {
        foreach ([
            'nomor_manifest',
            'tanggal_manifest',
            'nama_pengirim',
            'alamat_pengirim',
            'nama_fasilitas_penyimpanan',
            'penanggung_jawab_pengirim',
            'jabatan_pj_pengirim',
            'kode_limbah',
            'nama_limbah',
            'nama_teknik',
            'periode_limbah_mulai',
            'periode_limbah_selesai',
            'karakteristik_limbah',
            'jenis_kemasan',
            'jumlah_kemasan',
            'jumlah_limbah_ton',
            'keterangan_tambahan',
            'tujuan_pengangkutan',
            'nama_pengangkut',
            'alamat_pengangkut',
            'no_telepon_darurat',
            'jumlah_ril',
            'identitas_alat_angkut',
            'waktu_mulai_pengangkutan',
            'waktu_selesai_pengangkutan',
            'penanggung_jawab_pengangkut',
            'jabatan_pj_pengangkut',
            'nama_penerima',
            'alamat_penerima',
            'no_telepon_penerima',
            'jenis_pengelolaan',
            'jumlah_diterima_kg',
            'penanggung_jawab_penerima',
            'jabatan_pj_penerima',
        ] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $numericFields
     */
    protected function normalizeNumericList(mixed $list, array $numericFields): mixed
    {
        if (! is_array($list)) {
            return $list;
        }

        foreach ($list as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('entries', $row) && is_array($row['entries'])) {
                foreach ($row['entries'] as $entryIndex => $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }

                    foreach ($numericFields as $field) {
                        if (! array_key_exists($field, $entry) || ! is_string($entry[$field])) {
                            continue;
                        }

                        $row['entries'][$entryIndex][$field] = str_replace(',', '.', trim($entry[$field]));
                    }
                }
            }

            foreach ($numericFields as $field) {
                if (! array_key_exists($field, $row) || ! is_string($row[$field])) {
                    continue;
                }

                $row[$field] = str_replace(',', '.', trim($row[$field]));
            }

            $list[$index] = $row;
        }

        return $list;
    }
}
