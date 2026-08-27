<?php

namespace App\Http\Requests\Inventaris;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLokasiRequest extends FormRequest
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
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'jenis_lokasi' => ['required', Rule::in(['Gedung', 'Laboratorium', 'Ruangan'])],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_lokasi.required' => 'Nama lokasi wajib diisi.',
            'jenis_lokasi.required' => 'Jenis lokasi wajib dipilih.',
            'jenis_lokasi.in' => 'Jenis lokasi tidak valid.',
        ];
    }
}
