<?php

namespace App\Http\Requests\Inventaris;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTitikIpamRequest extends FormRequest
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
        $unitId = $this->input('unit_ipam_id');

        return [
            'unit_ipam_id' => ['required', 'integer', Rule::exists('unit_ipam', 'id')],
            'titik_lokasi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('titik_ipam', 'titik_lokasi')->where('unit_ipam_id', $unitId),
            ],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_ipam_id.required' => 'Unit IPAM wajib dipilih.',
            'unit_ipam_id.exists' => 'Unit IPAM tidak valid.',
            'titik_lokasi.required' => 'Nama titik wajib diisi.',
            'titik_lokasi.unique' => 'Nama titik sudah ada di unit IPAM ini.',
        ];
    }
}
