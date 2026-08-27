<?php

namespace App\Http\Requests\Inventaris;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        /** @var User $target */
        $target = $this->route('user');

        return [
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($target->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(User::roles())],
            'lokasi_id' => [
                'nullable',
                'integer',
                Rule::exists('lokasi', 'id'),
                Rule::requiredIf(fn () => $this->input('role') === 'Kalab'),
                Rule::prohibitedIf(fn () => $this->input('role') !== 'Kalab'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh huruf, angka, titik, strip, dan garis bawah.',
            'password.min' => 'Password minimal 8 karakter.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'lokasi_id.required' => 'Lab/lokasi wajib dipilih untuk role Kalab.',
            'lokasi_id.exists' => 'Lokasi tidak ditemukan.',
            'lokasi_id.prohibited' => 'Lokasi hanya untuk user dengan role Kalab.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') !== 'Kalab') {
            $this->merge(['lokasi_id' => null]);
        }
    }
}
