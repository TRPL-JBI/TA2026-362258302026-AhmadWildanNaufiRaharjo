<?php

namespace App\Http\Requests\Pemantauan;

use App\Support\IpalTriwulan;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaporanIpalRequest extends FormRequest
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
        $allBulan = array_unique(array_merge(...array_values(IpalTriwulan::triwulanToBulanMap())));

        return [
            'triwulan_key' => ['required', 'string', Rule::in(IpalTriwulan::triwulanKeys())],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan_list' => ['required', 'array', 'min:1'],
            'bulan_list.*.nama' => ['required', 'string', Rule::in($allBulan)],
            'bulan_list.*.catatan' => ['required', 'array'],
            'bulan_list.*.catatan.*.tanggal' => ['required', 'date'],
            'bulan_list.*.catatan.*.debit_in' => ['required', 'numeric', 'min:0'],
            'bulan_list.*.catatan.*.debit_out' => ['required', 'numeric', 'min:0'],
            'bulan_list.*.catatan.*.ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'bulan_list.*.catatan.*.suhu' => ['required', 'numeric', 'min:-10', 'max:100'],
            'evaluasi' => ['nullable', 'array'],
            'evaluasi.jenis_dampak' => ['nullable', 'string', 'max:200'],
            'evaluasi.sumber_dampak' => ['nullable', 'string'],
            'evaluasi.parameter_pemantauan' => ['nullable', 'string'],
            'evaluasi.tolak_ukur' => ['nullable', 'string'],
            'evaluasi.lokasi_pengelolaan' => ['nullable', 'string', 'max:200'],
            'evaluasi.evaluasi_hasil' => ['nullable', 'string'],
            'evaluasi.tindakan_perbaikan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'triwulan_key.required' => 'Pilih periode triwulan.',
            'triwulan_key.in' => 'Periode triwulan tidak valid.',
            'tahun.required' => 'Tahun wajib dipilih.',
            'bulan_list.required' => 'Data catatan harian wajib diisi.',
            'bulan_list.*.catatan.required' => 'Setiap bulan minimal memiliki satu catatan.',
            'bulan_list.*.catatan.*.tanggal.required' => 'Tanggal sampling wajib diisi.',
            'bulan_list.*.catatan.*.debit_in.required' => 'Debit input wajib diisi.',
            'bulan_list.*.catatan.*.debit_out.required' => 'Debit output wajib diisi.',
            'bulan_list.*.catatan.*.ph.required' => 'Nilai pH wajib diisi.',
            'bulan_list.*.catatan.*.ph.numeric' => 'Nilai pH harus berupa angka.',
            'bulan_list.*.catatan.*.ph.min' => 'Nilai pH minimal 0.',
            'bulan_list.*.catatan.*.ph.max' => 'Nilai pH maksimal 14.',
            'bulan_list.*.catatan.*.suhu.required' => 'Suhu wajib diisi.',
            'bulan_list.*.catatan.*.suhu.numeric' => 'Suhu harus berupa angka.',
            'bulan_list.*.catatan.*.suhu.min' => 'Suhu minimal -10°C.',
            'bulan_list.*.catatan.*.suhu.max' => 'Suhu maksimal 100°C.',
            'bulan_list.min' => 'Minimal satu catatan harian wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $bulanList = $this->input('bulan_list', []);

        if (! is_array($bulanList)) {
            return;
        }

        foreach ($bulanList as $bulanIndex => $bulan) {
            if (! is_array($bulan['catatan'] ?? null)) {
                continue;
            }

            foreach ($bulan['catatan'] as $catatanIndex => $catatan) {
                if (! is_array($catatan)) {
                    continue;
                }

                foreach (['debit_in', 'debit_out', 'ph', 'suhu'] as $field) {
                    if (! array_key_exists($field, $catatan)) {
                        continue;
                    }

                    $value = $catatan[$field];
                    if (! is_string($value)) {
                        continue;
                    }

                    $bulanList[$bulanIndex]['catatan'][$catatanIndex][$field] = str_replace(',', '.', trim($value));
                }
            }
        }

        $this->merge(['bulan_list' => $bulanList]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $bulanList = $this->input('bulan_list', []);
            $totalCatatan = 0;

            foreach ($bulanList as $bulan) {
                $totalCatatan += count($bulan['catatan'] ?? []);
            }

            if ($totalCatatan === 0) {
                $validator->errors()->add('bulan_list', 'Minimal satu catatan harian wajib diisi.');
            }
        });
    }
}
