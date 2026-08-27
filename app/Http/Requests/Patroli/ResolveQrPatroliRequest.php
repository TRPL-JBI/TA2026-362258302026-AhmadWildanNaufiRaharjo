<?php

namespace App\Http\Requests\Patroli;

use Illuminate\Foundation\Http\FormRequest;

class ResolveQrPatroliRequest extends FormRequest
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
            'q' => ['required', 'string', 'max:10000'],
            'scan_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ];
    }
}
