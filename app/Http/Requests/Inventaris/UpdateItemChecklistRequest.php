<?php

namespace App\Http\Requests\Inventaris;

use App\Support\ChecklistTemuanAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemChecklistRequest extends FormRequest
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
            'nama_item' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'probability' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'severity' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_item.required' => 'Nama item bahaya wajib diisi.',
            'probability.required' => 'Nilai probability wajib dipilih.',
            'probability.in' => 'Nilai probability tidak valid.',
            'severity.required' => 'Nilai severity wajib dipilih.',
            'severity.in' => 'Nilai severity tidak valid.',
        ];
    }
}
