<?php

namespace App\Http\Requests\Sop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UpdateSopDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Petugas K3LH';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function pdfFile(): ?UploadedFile
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }
}
