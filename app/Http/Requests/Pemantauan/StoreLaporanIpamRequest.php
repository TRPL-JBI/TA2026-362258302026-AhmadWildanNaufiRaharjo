<?php

namespace App\Http\Requests\Pemantauan;

use App\Support\IpamAltFormat;
use App\Support\IpamBulan;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaporanIpamRequest extends FormRequest
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
        return $this->sharedRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->sharedMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNumericFields();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $units = $this->input('units', []);
            $filledRows = 0;

            if (! is_array($units)) {
                return;
            }

            foreach ($units as $unitIndex => $unit) {
                if (! is_array($unit['minggu_list'] ?? null)) {
                    continue;
                }

                foreach ($unit['minggu_list'] as $mingguIndex => $minggu) {
                    if (! is_array($minggu['data_titik'] ?? null)) {
                        continue;
                    }

                    foreach ($minggu['data_titik'] as $titikIndex => $titik) {
                        if (! is_array($titik)) {
                            continue;
                        }

                        $hasAny = $this->titikRowHasAny($titik);
                        $hasAll = $this->titikRowIsComplete($titik);

                        if ($hasAny && ! $hasAll) {
                            $validator->errors()->add(
                                "units.{$unitIndex}.minggu_list.{$mingguIndex}.data_titik.{$titikIndex}",
                                'Jika salah satu field diisi, semua field (pH, ALT, Salmonella, Status) wajib dilengkapi.',
                            );

                            continue;
                        }

                        if ($hasAll) {
                            $filledRows++;
                        }
                    }
                }
            }

            if ($filledRows === 0) {
                $validator->errors()->add('units', 'Minimal isi data lengkap untuk satu titik.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            'bulan' => ['required', 'string', Rule::in(IpamBulan::bulanOptions())],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.unit_id' => ['required', 'integer', 'exists:unit_ipam,id'],
            'units.*.minggu_list' => ['required', 'array', 'min:1'],
            'units.*.minggu_list.*.minggu_ke' => ['required', 'integer', 'min:1', 'max:5'],
            'units.*.minggu_list.*.data_titik' => ['nullable', 'array'],
            'units.*.minggu_list.*.data_titik.*.titik_id' => ['required', 'integer', 'exists:titik_ipam,id'],
            'units.*.minggu_list.*.data_titik.*.ph' => ['nullable', 'numeric', 'min:0', 'max:14'],
            'units.*.minggu_list.*.data_titik.*.alt' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (! is_string($value) || ! IpamAltFormat::isValid($value)) {
                        $fail('Format ALT tidak valid. Contoh: 5,50 x 10²');
                    }
                },
            ],
            'units.*.minggu_list.*.data_titik.*.salmonella' => ['nullable', Rule::in(['Negatif', 'Positif'])],
            'units.*.minggu_list.*.data_titik.*.status' => ['nullable', Rule::in(['Baik', 'Tidak Baik'])],
            'notes' => ['nullable', 'array'],
            'notes.kendala' => ['nullable', 'string'],
            'notes.rekomendasi' => ['nullable', 'string'],
            'notes.kesimpulan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sharedMessages(): array
    {
        return [
            'bulan.required' => 'Bulan wajib dipilih.',
            'bulan.in' => 'Bulan tidak valid.',
            'tahun.required' => 'Tahun wajib dipilih.',
            'units.required' => 'Minimal satu unit IPAM wajib diisi.',
            'units.min' => 'Minimal satu unit IPAM wajib diisi.',
            'units.*.minggu_list.required' => 'Setiap unit minimal memiliki satu minggu pemantauan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $titik
     */
    protected function titikRowHasAny(array $titik): bool
    {
        foreach (['ph', 'alt', 'salmonella', 'status'] as $field) {
            $value = $titik[$field] ?? null;
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $titik
     */
    protected function titikRowIsComplete(array $titik): bool
    {
        if (! $this->titikRowHasAny($titik)) {
            return false;
        }

        if (! isset($titik['ph']) || $titik['ph'] === '') {
            return false;
        }

        $alt = trim((string) ($titik['alt'] ?? ''));
        if ($alt === '' || ! IpamAltFormat::isValid($alt)) {
            return false;
        }

        $salmonella = $titik['salmonella'] ?? '';
        $status = $titik['status'] ?? '';

        return $salmonella !== '' && $status !== '';
    }

    protected function normalizeNumericFields(): void
    {
        $units = $this->input('units', []);

        if (! is_array($units)) {
            return;
        }

        foreach ($units as $unitIndex => $unit) {
            if (! is_array($unit['minggu_list'] ?? null)) {
                continue;
            }

            foreach ($unit['minggu_list'] as $mingguIndex => $minggu) {
                if (! is_array($minggu['data_titik'] ?? null)) {
                    continue;
                }

                foreach ($minggu['data_titik'] as $titikIndex => $titik) {
                    if (! is_array($titik)) {
                        continue;
                    }

                    if (array_key_exists('ph', $titik) && is_string($titik['ph'])) {
                        $units[$unitIndex]['minggu_list'][$mingguIndex]['data_titik'][$titikIndex]['ph'] =
                            str_replace(',', '.', trim($titik['ph']));
                    }

                    if (array_key_exists('alt', $titik) && is_string($titik['alt'])) {
                        $units[$unitIndex]['minggu_list'][$mingguIndex]['data_titik'][$titikIndex]['alt'] =
                            IpamAltFormat::normalize($titik['alt']);
                    }
                }
            }
        }

        $this->merge(['units' => $units]);
    }
}
