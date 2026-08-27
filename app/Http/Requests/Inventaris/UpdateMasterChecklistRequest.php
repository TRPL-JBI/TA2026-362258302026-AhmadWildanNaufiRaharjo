<?php

namespace App\Http\Requests\Inventaris;

use App\Support\ChecklistTemuanAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMasterChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ChecklistTemuanAccess::canManage($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_checklist' => ['required', 'string', 'max:100'],
            'lokasi_id' => ['required', 'integer', 'exists:lokasi,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_checklist.required' => 'Nama checklist wajib diisi.',
            'lokasi_id.required' => 'Lokasi wajib dipilih.',
            'lokasi_id.exists' => 'Lokasi tidak ditemukan.',
        ];
    }
}
