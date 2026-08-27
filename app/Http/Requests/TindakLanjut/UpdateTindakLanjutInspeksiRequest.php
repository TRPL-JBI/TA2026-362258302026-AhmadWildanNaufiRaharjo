<?php

namespace App\Http\Requests\TindakLanjut;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTindakLanjutInspeksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:Menunggu Tindakan,Dalam Proses,Selesai'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ];
    }
}
