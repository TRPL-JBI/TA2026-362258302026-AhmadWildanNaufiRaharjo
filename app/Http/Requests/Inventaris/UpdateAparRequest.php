<?php

namespace App\Http\Requests\Inventaris;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAparRequest extends FormRequest
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
        return [
            'lokasi_id' => ['required', 'integer', Rule::exists('lokasi', 'id')],
            'jenis_apar' => ['required', Rule::in(['Powder', 'CO2', 'Foam'])],
            'kapasitas_kg' => ['required', 'numeric', 'min:0.1', 'max:999.99'],
            'tanggal_expired' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lokasi_id.required' => 'Lokasi penempatan wajib dipilih.',
            'lokasi_id.exists' => 'Lokasi tidak valid.',
            'jenis_apar.required' => 'Jenis APAR wajib dipilih.',
            'jenis_apar.in' => 'Jenis APAR tidak valid.',
            'kapasitas_kg.required' => 'Kapasitas wajib diisi.',
            'kapasitas_kg.min' => 'Kapasitas minimal 0,1 kg.',
            'tanggal_expired.required' => 'Tanggal expired wajib diisi.',
        ];
    }
}
